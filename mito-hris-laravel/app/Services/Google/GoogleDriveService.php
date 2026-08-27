<?php

namespace App\Services\Google;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected GoogleClientFactory $factory;

    public function __construct(GoogleClientFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Upload an UploadedFile instance to a specific Google Drive folder.
     */
    public function uploadUploadedFile(UploadedFile $file, ?string $folderId = null, ?string $customFilename = null): ?array
    {
        $folderId = $folderId ?: config('google.drive.docs_folder_id');
        $filename = $customFilename ?: $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $content  = file_get_contents($file->getRealPath());

        return $this->uploadRawContent($content, $filename, $mimeType, $folderId);
    }

    /**
     * Upload binary content (e.g. generated PDF) directly to Google Drive folder.
     */
    public function uploadRawContent(string $content, string $filename, string $mimeType, ?string $folderId = null): ?array
    {
        try {
            $service = $this->factory->getDriveService();

            $fileMetadata = new DriveFile([
                'name'    => $filename,
                'parents' => $folderId ? [$folderId] : [],
            ]);

            $uploadedFile = $service->files->create($fileMetadata, [
                'data'       => $content,
                'mimeType'   => $mimeType,
                'uploadType' => 'multipart',
                'fields'     => 'id, name, webViewLink, webContentLink',
                'supportsAllDrives' => true,
            ]);

            // Make file publicly readable via link
            try {
                $permission = new Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
                $service->permissions->create($uploadedFile->id, $permission);
            } catch (\Throwable $permEx) {
                Log::warning("GoogleDriveService::permission warning: " . $permEx->getMessage());
            }

            return [
                'file_id'      => $uploadedFile->id,
                'name'         => $uploadedFile->name,
                'view_url'     => $uploadedFile->webViewLink,
                'download_url' => $uploadedFile->webContentLink ?? "https://drive.google.com/uc?id={$uploadedFile->id}&export=download",
            ];
        } catch (\Throwable $e) {
            Log::error("GoogleDriveService::uploadRawContent error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the Google Drive service instance.
     */
    public function getDriveService(): \Google\Service\Drive
    {
        return $this->factory->getDriveService();
    }

    /**
     * Delete a file from Google Drive by File ID.
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $service = $this->factory->getDriveService();
            $service->files->delete($fileId);
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleDriveService::deleteFile error on {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get or create a folder by name within a parent folder.
     * 1:1 with GAS getOrCreateOffboardingFolder_() behavior.
     */
    public function getOrCreateFolder(string $folderName, ?string $parentFolderId = null): ?array
    {
        try {
            $service = $this->factory->getDriveService();

            // Search for existing folder with same name in parent
            $q = "name = '" . addslashes($folderName) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            if ($parentFolderId) {
                $q .= " and '" . $parentFolderId . "' in parents";
            }

            $results = $service->files->listFiles([
                'q'                     => $q,
                'fields'                => 'files(id, name, webViewLink)',
                'spaces'                => 'drive',
                'supportsAllDrives'     => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $results->getFiles();
            if (!empty($files)) {
                $existing = $files[0];
                return [
                    'folder_id'  => $existing->id,
                    'name'       => $existing->name,
                    'view_url'   => $existing->webViewLink ?? "https://drive.google.com/drive/folders/{$existing->id}",
                ];
            }

            // Create new folder
            $metadata = new DriveFile([
                'name'     => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => $parentFolderId ? [$parentFolderId] : [],
            ]);

            $folder = $service->files->create($metadata, [
                'fields'            => 'id, name, webViewLink',
                'supportsAllDrives' => true,
            ]);

            // Make folder readable
            try {
                $permission = new Permission(['type' => 'anyone', 'role' => 'reader']);
                $service->permissions->create($folder->id, $permission);
            } catch (\Throwable $permEx) {
                Log::warning("GoogleDriveService::getOrCreateFolder permission warning: " . $permEx->getMessage());
            }

            return [
                'folder_id' => $folder->id,
                'name'      => $folder->name,
                'view_url'  => $folder->webViewLink ?? "https://drive.google.com/drive/folders/{$folder->id}",
            ];
        } catch (\Throwable $e) {
            Log::error("GoogleDriveService::getOrCreateFolder error '{$folderName}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload offboarding attachment documents to a per-employee folder under
     * the "MITO HRIS Offboarding" root folder. Mirrors GAS uploadOffboardingDocuments_().
     *
     * @param  string       $employeeId
     * @param  string       $fullName
     * @param  UploadedFile[] $files          Associative: ['doc_type' => UploadedFile, ...]
     * @param  string|null  $existingFolderUrl  Already-created folder URL (reuse if set)
     * @return array{success:bool, folder_id:string|null, folder_url:string|null, uploaded:array, links_string:string}
     */
    public function uploadOffboardingDocuments(
        string $employeeId,
        string $fullName,
        array  $files,
        ?string $existingFolderUrl = null
    ): array {
        $uploaded = [];
        $rootFolderId = config('google.drive.offboarding_folder_id')
            ?: config('google.drive.docs_folder_id')
            ?: null;

        // Step 1: Get/create root "MITO HRIS Offboarding" folder
        $rootFolder = $this->getOrCreateFolder('MITO HRIS Offboarding', $rootFolderId);
        if (!$rootFolder) {
            return [
                'success'      => false,
                'folder_id'    => null,
                'folder_url'   => null,
                'uploaded'     => [],
                'links_string' => '',
                'message'      => 'Folder Google Drive tidak dapat diakses. Bagikan folder kepada mito-hris@hris-maha.iam.gserviceaccount.com dengan akses Editor.',
            ];
        }

        // Step 2: Get/create per-employee folder — reuse if existing link provided
        $empFolderName = preg_replace('/[^a-zA-Z0-9 _\-]/', '', "{$employeeId}_{$fullName}");
        $empFolder     = null;

        if ($existingFolderUrl) {
            // Try to extract folder ID from existing URL and verify it exists
            if (preg_match('/folders\/([a-zA-Z0-9_\-]+)/', $existingFolderUrl, $m)) {
                $empFolder = [
                    'folder_id' => $m[1],
                    'view_url'  => $existingFolderUrl,
                ];
            }
        }

        if (!$empFolder) {
            $empFolder = $this->getOrCreateFolder($empFolderName, $rootFolder['folder_id']);
        }

        if (!$empFolder) {
            return [
                'success'      => false,
                'folder_id'    => $rootFolder['folder_id'],
                'folder_url'   => $rootFolder['view_url'],
                'uploaded'     => [],
                'links_string' => '',
                'message'      => 'Gagal membuat folder karyawan di Google Drive.',
            ];
        }

        // Step 3: Upload each file into the employee folder
        $now = now()->timezone('Asia/Jakarta')->format('Ymd_His');
        $links = [];

        foreach ($files as $docType => $uploadedFile) {
            if (!($uploadedFile instanceof UploadedFile) || !$uploadedFile->isValid()) {
                continue;
            }
            $safeType = preg_replace('/[^a-zA-Z0-9]/', '_', $docType);
            $ext      = $uploadedFile->getClientOriginalExtension() ?: 'pdf';
            $filename = "{$safeType}_{$now}.{$ext}";
            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
            $content  = file_get_contents($uploadedFile->getRealPath());

            $result = $this->uploadRawContent($content, $filename, $mimeType, $empFolder['folder_id']);
            if ($result) {
                $uploaded[] = [
                    'type'     => $docType,
                    'fileName' => $filename,
                    'view_url' => $result['view_url'],
                    'file_id'  => $result['file_id'],
                ];
                $links[] = "{$docType}: " . $result['view_url'];
            }
        }

        $uploadSuccess = count($uploaded) === count($files);

        return [
            'success'      => $uploadSuccess,
            'folder_id'    => $empFolder['folder_id'],
            'folder_url'   => $empFolder['view_url'] ?? "https://drive.google.com/drive/folders/{$empFolder['folder_id']}",
            'uploaded'     => $uploaded,
            'links_string' => implode("\n", $links),
            'message'      => $uploadSuccess
                ? null
                : 'Folder berhasil dibuat, tetapi file gagal di-upload. Service account memerlukan Shared Drive atau OAuth user dengan storage quota.',
        ];
    }
}
