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
}
