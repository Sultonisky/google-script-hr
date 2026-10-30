<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\EmployeeDocumentArchiveService;
use App\Services\WarningLetterSanctionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Register of issued employee documents (SK, kontrak, paklaring) from
 * Employee_Documents, joined with employee master data, with PDF re-download.
 */
class DocumentTrackingController extends Controller
{
    private const ENTITIES = ['MSI', 'SPI', 'PII', 'MEP'];

    private const PERIODS = ['this_month', 'last_30', 'this_year'];

    private const SORTS = ['issued_desc', 'issued_asc', 'nomor_asc', 'nomor_desc', 'name_asc', 'name_desc'];

    private const WARNING_LETTER_SORTS = ['issued_desc', 'issued_asc', 'nomor_asc', 'nomor_desc', 'name_asc', 'name_desc'];

    private const WARNING_LETTER_STATUSES = ['active', 'superseded', 'ended'];

    public function __construct(
        protected EmployeeDocumentRepositoryInterface $documentRepo,
        protected EmployeeRepositoryInterface $employeeRepo,
        protected EmployeeDocumentArchiveService $documentArchive,
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo,
        protected WarningLetterSanctionService $sanctions,
    ) {}

    /**
     * Re-download the archived PDF of an issued document; documents issued before
     * archiving existed are regenerated from their own nomor + issue date.
     */
    public function download(string $documentId): Response|RedirectResponse
    {
        try {
            $file = $this->documentArchive->download($documentId, session('hr_user.email', 'HR Administrator'));
        } catch (\RuntimeException $e) {
            return redirect()->route('hr.documents.index')->with('document_download_error', $e->getMessage());
        }

        if (!$file) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file['file_name']) ?: 'dokumen.pdf';

        return new Response($file['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $file['file_name'], $fallbackName),
            'Content-Length' => strlen($file['content']),
            'Cache-Control' => 'private, no-store',
            'X-Document-Source' => $file['source'],
        ]);
    }

    public function index(Request $request): View
    {
        $perPage = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));
        $now = now()->timezone('Asia/Jakarta');

        $documents = $this->loadDocuments()
            ->reject(fn ($document) => $document->docCode === SkDocumentType::SURAT_PERINGATAN->value)
            ->values();

        $stats = [
            'total' => $documents->count(),
            'this_month' => $documents->filter(
                fn ($d) => $d->issuedAtParsed?->isSameMonth($now) ?? false
            )->count(),
            'employees' => $documents->pluck('employeeId')->filter()->unique()->count(),
            'contracts' => $documents->filter(
                fn ($d) => SkDocumentType::tryFrom($d->docCode)?->isContract() ?? false
            )->count(),
        ];

        $searchFilter = trim((string) $request->query('search', ''));
        $typeFilter = strtoupper(trim((string) $request->query('type', '')));
        $typeFilter = SkDocumentType::tryFrom($typeFilter)
            && $typeFilter !== SkDocumentType::SURAT_PERINGATAN->value
                ? $typeFilter
                : '';
        $entityFilter = strtoupper(trim((string) $request->query('entity', '')));
        $entityFilter = in_array($entityFilter, self::ENTITIES, true) ? $entityFilter : '';
        $periodFilter = (string) $request->query('period', '');
        $periodFilter = in_array($periodFilter, self::PERIODS, true) ? $periodFilter : '';
        $sortFilter = (string) $request->query('sort', 'issued_desc');
        $sortFilter = in_array($sortFilter, self::SORTS, true) ? $sortFilter : 'issued_desc';

        $filtered = $documents;

        if ($searchFilter !== '') {
            $search = strtolower($searchFilter);
            $filtered = $filtered->filter(fn ($d) => str_contains(strtolower($d->nomor), $search)
                || str_contains(strtolower($d->documentId), $search)
                || str_contains(strtolower($d->employeeId), $search)
                || str_contains(strtolower($d->employeeName), $search)
                || str_contains(strtolower($d->reference), $search));
        }

        if ($typeFilter !== '') {
            $filtered = $filtered->filter(fn ($d) => $d->docCode === $typeFilter);
        }

        if ($entityFilter !== '') {
            $filtered = $filtered->filter(fn ($d) => $d->entity === $entityFilter);
        }

        if ($periodFilter !== '') {
            $from = match ($periodFilter) {
                'this_month' => $now->copy()->startOfMonth(),
                'last_30' => $now->copy()->subDays(30)->startOfDay(),
                'this_year' => $now->copy()->startOfYear(),
            };
            $filtered = $filtered->filter(fn ($d) => $d->issuedAtParsed !== null && $d->issuedAtParsed->gte($from));
        }

        $filtered = match ($sortFilter) {
            'issued_asc' => $filtered->sortBy(fn ($d) => $d->issuedAtParsed?->timestamp ?? PHP_INT_MAX),
            'nomor_asc' => $filtered->sortBy(fn ($d) => strtolower($d->nomor)),
            'nomor_desc' => $filtered->sortByDesc(fn ($d) => strtolower($d->nomor)),
            'name_asc' => $filtered->sortBy(fn ($d) => strtolower($d->employeeName)),
            'name_desc' => $filtered->sortByDesc(fn ($d) => strtolower($d->employeeName)),
            default => $filtered->sortByDesc(fn ($d) => $d->issuedAtParsed?->timestamp ?? 0),
        };
        $filtered = $filtered->values();

        $total = $filtered->count();
        $offset = ($currentPage - 1) * $perPage;
        $rows = $filtered->slice($offset, $perPage)->values();
        $archiveSources = $this->documentArchive->archiveSources($rows->pluck('documentId')->all());

        $documentTypes = collect(SkDocumentType::cases())
            ->reject(fn (SkDocumentType $type) => $type === SkDocumentType::SURAT_PERINGATAN)
            ->mapWithKeys(fn (SkDocumentType $type) => [$type->value => $type->label()])
            ->all();
        $entities = self::ENTITIES;

        return view('hr.documents.index', compact(
            'rows',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'typeFilter',
            'entityFilter',
            'periodFilter',
            'sortFilter',
            'documentTypes',
            'entities',
            'archiveSources'
        ));
    }

    public function warningLetters(Request $request): View
    {
        $perPage = in_array((int) $request->query('per_page', 10), [10, 25, 50], true)
            ? (int) $request->query('per_page', 10)
            : 10;
        $currentPage = max(1, (int) $request->query('page', 1));
        $searchFilter = trim((string) $request->query('search', ''));
        $sortFilter = (string) $request->query('sort', 'issued_desc');
        $sortFilter = in_array($sortFilter, self::WARNING_LETTER_SORTS, true) ? $sortFilter : 'issued_desc';
        $statusFilter = (string) $request->query('status', '');
        $statusFilter = in_array($statusFilter, self::WARNING_LETTER_STATUSES, true) ? $statusFilter : '';
        $levelFilter = strtoupper(trim((string) $request->query('level', '')));
        $levelFilter = WarningLetterLevel::tryFrom($levelFilter)?->value ?? '';
        $now = now()->timezone('Asia/Jakarta');

        $allWarningLetters = $this->loadDocuments()
            ->filter(fn ($document) => $document->docCode === SkDocumentType::SURAT_PERINGATAN->value)
            ->values();
        $sanctionStates = $this->sanctions->statesFor($allWarningLetters
            ->map(fn ($document) => [
                'key' => $document->documentId !== '' ? $document->documentId : $document->employeeId.'|'.$document->nomor,
                'employee_id' => $document->employeeId,
                'reference' => $document->reference,
                'notes' => $document->notes,
                'doc_type' => $document->docType,
                'nomor' => $document->nomor,
                'issued_at' => $document->issuedAtParsed,
            ])
            ->all());
        $allWarningLetters->each(function ($document) use ($sanctionStates) {
            $key = $document->documentId !== '' ? $document->documentId : $document->employeeId.'|'.$document->nomor;
            $state = $sanctionStates[$key];
            $document->sanctionLevel = $state['level'];
            $document->validUntilLabel = $state['valid_until_label'];
            $document->sanctionActive = $state['active'];
            $document->sanctionSuperseded = $state['superseded'];
            $document->sanctionStatus = $state['superseded']
                ? 'superseded'
                : ($state['active'] ? 'active' : ($state['valid_until_label'] !== '' ? 'ended' : ''));
            $document->levelCode = WarningLetterLevel::resolve(
                $document->reference,
                $document->docType,
                $document->nomor
            )?->value ?? '';
        });
        $activeEmployees = $this->sanctions->activeBadgesFromLetters($allWarningLetters
            ->map(fn ($document) => [
                'employee_id' => $document->employeeId,
                'reference' => $document->reference,
                'notes' => $document->notes,
                'doc_type' => $document->docType,
                'nomor' => $document->nomor,
                'issued_at' => $document->issuedAtParsed,
            ])
            ->values()
            ->all());
        $stats = [
            'total' => $allWarningLetters->count(),
            'this_month' => $allWarningLetters->filter(
                fn ($document) => $document->issuedAtParsed?->isSameMonth($now) ?? false
            )->count(),
            'employees' => $allWarningLetters->pluck('employeeId')->filter()->unique()->count(),
            'active' => count($activeEmployees),
        ];
        $statusCounts = [
            'active' => $allWarningLetters->where('sanctionStatus', 'active')->count(),
            'superseded' => $allWarningLetters->where('sanctionStatus', 'superseded')->count(),
            'ended' => $allWarningLetters->where('sanctionStatus', 'ended')->count(),
        ];
        $levelOptions = collect(WarningLetterLevel::cases())
            ->map(fn (WarningLetterLevel $level) => [
                'value' => $level->value,
                'label' => $level->shortLabel(),
                'count' => $allWarningLetters->where('levelCode', $level->value)->count(),
            ])
            ->all();
        $documents = $allWarningLetters;

        if ($statusFilter !== '') {
            $documents = $documents->filter(fn ($document) => $document->sanctionStatus === $statusFilter);
        }

        if ($levelFilter !== '') {
            $documents = $documents->filter(fn ($document) => $document->levelCode === $levelFilter);
        }

        if ($searchFilter !== '') {
            $search = mb_strtolower($searchFilter);
            $documents = $documents->filter(fn ($document) => str_contains(mb_strtolower($document->nomor), $search)
                || str_contains(mb_strtolower($document->employeeId), $search)
                || str_contains(mb_strtolower($document->employeeName), $search)
                || str_contains(mb_strtolower($document->reference), $search));
        }

        $documents = $documents
            ->when(
                $sortFilter === 'issued_asc',
                fn ($items) => $items->sortBy(fn ($document) => $document->issuedAtParsed?->timestamp ?? PHP_INT_MAX),
                fn ($items) => match ($sortFilter) {
                    'nomor_asc' => $items->sortBy(fn ($document) => strtolower($document->nomor)),
                    'nomor_desc' => $items->sortByDesc(fn ($document) => strtolower($document->nomor)),
                    'name_asc' => $items->sortBy(fn ($document) => strtolower($document->employeeName)),
                    'name_desc' => $items->sortByDesc(fn ($document) => strtolower($document->employeeName)),
                    default => $items->sortByDesc(fn ($document) => $document->issuedAtParsed?->timestamp ?? 0),
                }
            )
            ->values();
        $total = $documents->count();
        $rows = $documents->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return view('hr.warning-letters.tracking', compact(
            'rows',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'sortFilter',
            'statusFilter',
            'levelFilter',
            'statusCounts',
            'levelOptions'
        ));
    }

    /**
     * @return Collection<int, object>
     */
    private function loadDocuments(): Collection
    {
        $employees = $this->outsourceRepo->getAll()
            ->map(fn ($outsource) => $outsource->toEmployeeData())
            ->concat($this->employeeRepo->getAll())
            ->keyBy(fn ($employee) => ltrim(trim((string) ($employee->employeeId ?? '')), "'"));

        return $this->documentRepo->getAll()
            ->map(function (array $row) use ($employees) {
                $employeeId = ltrim(trim((string) ($row['Employee ID'] ?? '')), "'");
                $employee = $employees->get($employeeId);
                $docCode = strtoupper(trim((string) ($row['Doc Code'] ?? '')));
                $issuedAt = trim((string) ($row['Issued At'] ?? '')) ?: trim((string) ($row['Created At'] ?? ''));
                $issuedAtParsed = $this->issuedAtForDisplay($issuedAt, trim((string) ($row['Created At'] ?? '')));

                return (object) [
                    'documentId' => trim((string) ($row['Document ID'] ?? '')),
                    'employeeId' => $employeeId,
                    'employeeName' => trim((string) ($employee->fullName ?? '')),
                    'jobPosition' => trim((string) ($employee->jobPosition ?? '')),
                    'department' => trim((string) ($employee->department ?? '')),
                    'docCode' => $docCode,
                    'docType' => trim((string) ($row['Doc Type'] ?? ''))
                        ?: (SkDocumentType::tryFrom($docCode)?->label() ?? $docCode),
                    'nomor' => trim((string) ($row['Nomor'] ?? '')),
                    'entity' => strtoupper(trim((string) ($row['Entity'] ?? ''))),
                    'issuedAt' => $issuedAt,
                    'issuedAtParsed' => $issuedAtParsed,
                    'issuedAtHasTime' => $issuedAtParsed !== null && $issuedAtParsed->format('H:i:s') !== '00:00:00',
                    'issuedBy' => trim((string) ($row['Issued By'] ?? '')),
                    'reference' => trim((string) ($row['Reference'] ?? '')),
                    'notes' => trim((string) ($row['Notes'] ?? '')),
                    'points' => max(0, (int) ($row['Points'] ?? 0)),
                ];
            })
            ->values();
    }

    /**
     * A date-only document date is stored as midnight. Use the creation clock
     * so Tanggal Terbit does not show a fake 00:00.
     */
    private function issuedAtForDisplay(string $issuedAt, string $createdAt): ?Carbon
    {
        $parsed = $this->parseDate($issuedAt);
        if ($parsed === null || $parsed->format('H:i:s') !== '00:00:00') {
            return $parsed;
        }

        $created = $this->parseDate($createdAt);
        if ($created === null || $created->format('H:i:s') === '00:00:00') {
            return $parsed;
        }

        return $parsed->setTime($created->hour, $created->minute, $created->second);
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }
}
