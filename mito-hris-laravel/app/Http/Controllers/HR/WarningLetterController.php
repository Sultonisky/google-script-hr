<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\GenerateWarningLetterRequest;
use App\Models\EmployeeDocumentFile;
use App\Services\WarningLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

class WarningLetterController extends Controller
{
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
