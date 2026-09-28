<?php

namespace App\Http\Controllers\HR;

use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\EmployeeDocumentArchiveService;
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

    public function __construct(
        protected EmployeeDocumentRepositoryInterface $documentRepo,
        protected EmployeeRepositoryInterface $employeeRepo,
        protected EmployeeDocumentArchiveService $documentArchive,
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo,
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

        $documents = $this->loadDocuments();

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
        $typeFilter = SkDocumentType::tryFrom($typeFilter) ? $typeFilter : '';
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
                    'issuedAtParsed' => $this->parseDate($issuedAt),
                    'issuedBy' => trim((string) ($row['Issued By'] ?? '')),
                    'reference' => trim((string) ($row['Reference'] ?? '')),
                    'notes' => trim((string) ($row['Notes'] ?? '')),
                ];
            })
            ->values();
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
