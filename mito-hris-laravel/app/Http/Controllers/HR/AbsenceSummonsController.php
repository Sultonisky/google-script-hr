<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\GenerateAbsenceSummonsRequest;
use App\Models\EmployeeDocumentFile;
use App\Services\AbsenceSummonsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

class AbsenceSummonsController extends Controller
{
    public function __construct(private AbsenceSummonsService $absenceSummons) {}

    public function store(GenerateAbsenceSummonsRequest $request, string $id): JsonResponse
    {
        $result = $this->absenceSummons->generate(
            $id,
            $request->validated(),
            $this->hrUserName(),
            session('hr_user.email')
        );

        $status = $result['status'];
        unset($result['status']);

        if ($result['success']) {
            $result['pdf_url'] = route('hr.employees.absence-summons.download', [
                'id' => $id,
                'documentId' => $result['document_id'],
            ]);
        }

        return response()->json($result, $status);
    }

    public function download(string $id, string $documentId): Response
    {
        $file = EmployeeDocumentFile::query()
            ->where('document_id', $documentId)
            ->where('employee_id', ltrim(trim($id), "'"))
            ->where('doc_code', SkDocumentType::SURAT_PEMANGGILAN_MANGKIR->value)
            ->first();

        if (! $file) {
            abort(404, 'Surat Penggilan Mangkir tidak ditemukan.');
        }

        $content = $file->content();
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->file_name) ?: 'surat-penggilan-mangkir.pdf';

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $file->file_name, $fallbackName),
            'Content-Length' => strlen($content),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
