<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\GenerateWarningLetterRequest;
use App\Models\EmployeeDocumentFile;
use App\Services\CompanyRegulationBankService;
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

    public function __construct(
        private WarningLetterService $warningLetters,
        private CompanyRegulationBankService $regulationBank
    ) {}

    /**
     * Terbitkan Surat Peringatan dan arsipkan PDF-nya.
     *
     * POST /hr/employees/{id}/warning-letter
     * Requires: can:manage_employees
     */
    public function store(GenerateWarningLetterRequest $request, string $id): JsonResponse
    {
        $data = $this->canonicalizeBankReferences($request->validated());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $result = $this->warningLetters->generate(
            $id,
            $data,
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
        $data = $this->canonicalizeBankReferences($request->validated());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $result = $this->warningLetters->preview($id, $data);

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

    /**
     * Re-resolve selected references against the private PP bank before they enter the PDF.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|JsonResponse
     */
    private function canonicalizeBankReferences(array $data): array|JsonResponse
    {
        if (is_array($data['violations'] ?? null)) {
            foreach ($data['violations'] as $violationIndex => $violation) {
                foreach ($violation['references'] ?? [] as $referenceIndex => $reference) {
                    if (is_array($reference)) {
                        $resolved = $this->resolveBankReference(
                            $reference,
                            "violations.{$violationIndex}.references.{$referenceIndex}"
                        );
                        if ($resolved instanceof JsonResponse) {
                            return $resolved;
                        }
                        $data['violations'][$violationIndex]['references'][$referenceIndex] = $resolved;
                    }
                }
            }
        } elseif (is_array($data['regulation_references'] ?? null)) {
            foreach ($data['regulation_references'] as $referenceIndex => $reference) {
                if (is_array($reference)) {
                    $resolved = $this->resolveBankReference(
                        $reference,
                        "regulation_references.{$referenceIndex}"
                    );
                    if ($resolved instanceof JsonResponse) {
                        return $resolved;
                    }
                    $data['regulation_references'][$referenceIndex] = $resolved;
                }
            }
        }

        if ($this->hasBankReferenceSelection($data)) {
            $allReferences = is_array($data['violations'] ?? null)
                ? collect($data['violations'])->flatMap(fn (array $violation) => $violation['references'] ?? [])
                : collect($data['regulation_references'] ?? []);

            $data['regulation_reference'] = $allReferences
                ->map(fn (array $reference) => $this->formatBankReference($reference))
                ->filter()
                ->unique()
                ->implode('; ');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $reference
     * @return array<string, mixed>
     */
    private function resolveBankReference(array $reference, string $path): array|JsonResponse
    {
        if (trim((string) ($reference['chapter_number'] ?? '')) === '') {
            return $reference;
        }

        $resolved = $this->regulationBank->resolveReference($reference);
        if ($resolved === null) {
            return response()->json([
                'message' => 'Referensi Peraturan Perusahaan tidak valid.',
                'errors' => [
                    "{$path}.article_number" => ['Pilih Bab, Pasal, Ayat, dan huruf yang tersedia di Bank Peraturan Perusahaan.'],
                ],
            ], 422);
        }

        return array_merge($reference, $resolved);
    }

    private function hasBankReferenceSelection(array $data): bool
    {
        $references = is_array($data['violations'] ?? null)
            ? collect($data['violations'])->flatMap(fn (array $violation) => $violation['references'] ?? [])
            : collect($data['regulation_references'] ?? []);

        return $references->contains(fn (array $reference) => trim((string) ($reference['chapter_number'] ?? '')) !== '');
    }

    private function formatBankReference(array $reference): string
    {
        $parts = [];
        $chapterNumber = trim((string) ($reference['chapter_number'] ?? ''));
        $chapterTitle = trim((string) ($reference['chapter_title'] ?? ''));
        $articleNumber = trim((string) ($reference['article_number'] ?? ''));
        $articleTitle = trim((string) ($reference['article_title'] ?? ''));
        $paragraphNumber = trim((string) ($reference['paragraph_number'] ?? ''));
        $articleLetter = trim((string) ($reference['article_letter'] ?? ''));
        $subparagraphNumber = trim((string) ($reference['subparagraph_number'] ?? ''));
        $regulationType = trim((string) ($reference['regulation_type'] ?? ''));

        if ($chapterNumber !== '') {
            $parts[] = 'Bab '.$chapterNumber.($chapterTitle !== '' ? ' ('.$chapterTitle.')' : '');
        }
        if ($articleNumber !== '') {
            $parts[] = 'Pasal '.$articleNumber.($articleTitle !== '' ? ' ('.$articleTitle.')' : '');
        }
        if ($paragraphNumber !== '') {
            $parts[] = ctype_alpha($paragraphNumber)
                ? 'huruf '.strtolower($paragraphNumber)
                : 'ayat ('.$paragraphNumber.')';
        }
        if ($articleLetter !== '') {
            $parts[] = (ctype_alpha($paragraphNumber) ? 'subhuruf ' : 'huruf ').strtolower($articleLetter);
        }
        if ($subparagraphNumber !== '') {
            $parts[] = 'nomor '.$subparagraphNumber;
        }
        if ($regulationType !== '') {
            $parts[] = $regulationType;
        }

        return implode(' ', $parts);
    }
}
