<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\GenerateWarningLetterRequest;
use App\Models\EmployeeDocumentFile;
use App\Services\WarningLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class WarningLetterController extends Controller
{
    private const PREVIEW_CACHE_PREFIX = 'warning-letter-preview:';
    private const PREVIEW_TTL_MINUTES = 10;

    public function __construct(private WarningLetterService $warningLetters) {}

    /**
     * Terbitkan Surat Peringatan dan arsipkan PDF-nya.
     *
     * POST /hr/employees/{id}/warning-letter
     * Requires: can:manage_employees
     */
    public function store(GenerateWarningLetterRequest $request, string $id): JsonResponse
    {
        $result = $this->warningLetters->generate(
            $id,
            $request->validated(),
            $this->hrUserName(),
            session('hr_user.email')
        );

        $status = $result['status'];
        unset($result['status']);

        if ($result['success']) {
            $result['pdf_url'] = route('hr.employees.warning-letter.download', [
                'id' => $id,
                'documentId' => $result['document_id'],
            ]);
        }

        return response()->json($result, $status);
    }

    /**
     * Pratinjau draft PDF dari isi form; tidak menerbitkan nomor maupun mengarsipkan.
     *
     * POST /hr/employees/{id}/warning-letter/preview
     * Requires: can:manage_warning_letters
     */
    public function preview(GenerateWarningLetterRequest $request, string $id): JsonResponse
    {
        $result = $this->warningLetters->preview($id, $request->validated());

        if (!$result['success']) {
            $status = $result['status'];
            unset($result['status']);

            return response()->json($result, $status);
        }

        $token = Str::random(40);
        Cache::put(self::PREVIEW_CACHE_PREFIX.$token, [
            'employee_id' => ltrim(trim($id), "'"),
            'owner' => (string) session('hr_user.email'),
            'file_name' => $result['file_name'],
            'content' => base64_encode($result['content']),
        ], now()->addMinutes(self::PREVIEW_TTL_MINUTES));

        return response()->json([
            'success' => true,
            'preview_url' => route('hr.employees.warning-letter.preview.show', ['id' => $id, 'token' => $token]),
        ]);
    }

    /**
     * Tampilkan draft PDF hasil pratinjau (inline), hanya untuk pengguna yang membuatnya.
     *
     * GET /hr/employees/{id}/warning-letter/preview/{token}
     * Requires: can:manage_warning_letters
     */
    public function showPreview(string $id, string $token): Response
    {
        $draft = Cache::get(self::PREVIEW_CACHE_PREFIX.$token);
        if (!is_array($draft)
            || $draft['employee_id'] !== ltrim(trim($id), "'")
            || $draft['owner'] !== (string) session('hr_user.email')) {
            abort(404, 'Pratinjau Surat Peringatan sudah kedaluwarsa. Silakan buat pratinjau ulang.');
        }

        $content = base64_decode($draft['content']);

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $draft['file_name']),
            'Content-Length' => strlen($content),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Unduh PDF Surat Peringatan yang tersimpan di arsip.
     *
     * GET /hr/employees/{id}/warning-letter/{documentId}
     * Requires: can:manage_employees
     */
    public function download(string $id, string $documentId): Response
    {
        $file = EmployeeDocumentFile::query()
            ->where('document_id', $documentId)
            ->where('employee_id', ltrim(trim($id), "'"))
            ->where('doc_code', SkDocumentType::SURAT_PERINGATAN->value)
            ->first();

        if (!$file) {
            abort(404, 'Surat Peringatan tidak ditemukan.');
        }

        $content = $file->content();
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->file_name) ?: 'surat-peringatan.pdf';

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $file->file_name, $fallbackName),
            'Content-Length' => strlen($content),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
