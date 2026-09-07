<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreCertificationRequest;
use App\Http\Requests\HR\UpdateCertificationRequest;
use App\Models\Certification;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CertificationController extends Controller
{
    public function __construct(
        protected CertificationService $certService,
        protected AuditLogRepositoryInterface $auditRepo,
        protected EmployeeRepositoryInterface $employeeRepo,
    ) {}

    public function index(Request $request): View
    {
        $perPage    = max(1, (int) $request->query('per_page', 15));
        $currentPage = max(1, (int) $request->query('page', 1));

        $query = Certification::query()
            ->search($request->query('search'))
            ->ofType($request->query('type'))
            ->ofStatus($request->query('status'))
            ->ofEmployee($request->query('employee'));

        $total = (clone $query)->count();
        $offset = ($currentPage - 1) * $perPage;
        $certifications = (clone $query)->orderBy('id', 'desc')->skip($offset)->take($perPage)->get();

        $stats = $this->certService->getStats();

        $certIndexPath = route('certificates.portal.index');
        $certBasePath  = '/certifications';

        return view('hr.certifications.index', compact(
            'certifications',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'certIndexPath',
            'certBasePath',
        ));
    }

    public function store(StoreCertificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->actorEmail();

        if (empty($data['status'])) {
            $data['status'] = $this->certService->computeStatus($data['expiry_date'] ?? null)->value;
        }

        $data = $this->fillEmployeeFromProvider($data);
        $data = $this->normalizeClassificationFields($data);
        $data['attachment_path'] = $this->storeAttachment($data['attachment'] ?? null);
        unset($data['attachment']);

        $cert = Certification::create($data);

        $this->auditRepo->log(
            'Certification',
            (string) $cert->id,
            'created',
            null,
            null,
            $cert->name,
            $data['created_by'],
            'Certification Management'
        );

        return response()->json([
            'success' => true,
            'message' => 'Sertifikasi berhasil ditambahkan.',
            'certification' => $cert,
        ], 201);
    }

    public function show(Certification $certification): JsonResponse
    {
        return response()->json(['success' => true, 'certification' => $certification]);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): JsonResponse
    {
        $data = $request->validated();
        $uploadedAttachment = $data['attachment'] ?? null;
        $removeAttachment = (bool) ($data['remove_attachment'] ?? false);
        unset($data['attachment'], $data['remove_attachment']);

        // Mirror store(): when the form leaves status on "Auto" (omitted/empty),
        // derive it from the expiry date via the service. Explicit statuses
        // (incl. Suspended/Revoked) are respected and never overwritten.
        if (empty($data['status'] ?? null)) {
            $data['status'] = $this->certService->computeStatus(
                $data['expiry_date'] ?? $certification->expiry_date?->toDateString(),
                $certification->status,
            )->value;
        }

        $data = $this->fillEmployeeFromProvider($data);
        $data = $this->normalizeClassificationFields($data);

        if ($uploadedAttachment instanceof UploadedFile) {
            $this->deleteAttachment($certification->attachment_path);
            $data['attachment_path'] = $this->storeAttachment($uploadedAttachment);
        } elseif ($removeAttachment) {
            $this->deleteAttachment($certification->attachment_path);
            $data['attachment_path'] = null;
        }

        $old = $certification->only(array_keys($data));
        $certification->fill($data)->save();

        $actor = $this->actorEmail();
        foreach ($data as $field => $newValue) {
            $oldValue = $old[$field] ?? null;

            // Model accessors return cast objects (BackedEnum / Carbon), so
            // compare + log their primitive value, never the object itself.
            $oldPrimitive = $oldValue instanceof \BackedEnum ? $oldValue->value
                : ($oldValue instanceof \DateTimeInterface ? $oldValue->format('Y-m-d') : $oldValue);
            $newPrimitive = $newValue instanceof \BackedEnum ? $newValue->value
                : ($newValue instanceof \DateTimeInterface ? $newValue->format('Y-m-d') : $newValue);

            if ((string) ($oldPrimitive ?? '') !== (string) ($newPrimitive ?? '')) {
                $this->auditRepo->log(
                    'Certification',
                    (string) $certification->id,
                    'updated',
                    $field,
                    $oldPrimitive,
                    $newPrimitive,
                    $actor,
                    'Certification Management'
                );
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Sertifikasi berhasil diperbarui.',
            'certification' => $certification->fresh(),
        ]);
    }

    public function destroy(Certification $certification): JsonResponse
    {
        $actor = $this->actorEmail();
        $name = $certification->name;
        $id = (string) $certification->id;

        $certification->delete();
        $this->deleteAttachment($certification->attachment_path);

        $this->auditRepo->log(
            'Certification',
            $id,
            'deleted',
            null,
            null,
            $name,
            $actor,
            'Certification Management'
        );

        return response()->json([
            'success' => true,
            'message' => 'Sertifikasi berhasil dihapus.',
        ]);
    }

    public function generateCode(Certification $certification): JsonResponse
    {
        if (!empty(trim($certification->cert_code ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Sertifikasi sudah memiliki kode.',
            ], 422);
        }

        try {
            $certification->update(['cert_code' => $this->certService->generateCode()]);
            $actor = $this->actorEmail();
            $this->auditRepo->log(
                'Certification',
                (string) $certification->id,
                'code_generated',
                null,
                null,
                $certification->cert_code,
                $actor,
                'Certification Management'
            );

            return response()->json([
                'success' => true,
                'message' => "Kode sertifikasi {$certification->cert_code} berhasil digenerate.",
                'certification' => $certification->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function previewNextCode(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $this->certService->generateCode(),
        ]);
    }

    public function getJson(Certification $certification): JsonResponse
    {
        return response()->json(['success' => true, 'certification' => $certification]);
    }

    private function actorEmail(): string
    {
        $portal = request()->attributes->get('portal');
        $sessionKey = match ($portal) {
            'assets' => 'asset_auth',
            'certificates' => 'certificate_auth',
            'mpr' => config('mpr.session_key', 'mpr_requestor_auth'),
            default => 'hr_user',
        };

        return (string) session($sessionKey . '.email', 'HR Administrator');
    }

    /**
    * Serve a certification's stored PDF or image attachment.
     *
     * The path always comes from the DB record and must match the strict
    * attachment whitelist below, so this endpoint can never be used to
     * read arbitrary files on disk. Access is gated by can:view_certification
     * (group middleware).
     */
    public function attachment(Certification $certification): BinaryFileResponse
    {
        $path = $certification->attachment_path;

        if (
            !$path
            || !preg_match('#^certifications/[A-Za-z0-9_./-]+\.(pdf|jpe?g|png|webp)$#i', $path)
            || str_contains($path, '..')
        ) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
        ]);
    }

    private function storeAttachment(?UploadedFile $file): ?string
    {
        return $file?->store('certifications', 'local');
    }

    private function deleteAttachment(?string $path): void
    {
        if ($path && str_starts_with($path, 'certifications/') && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Backend source of truth for the employee fields: browser-supplied
     * employee_name / division / department are discarded and re-derived
     * from the active employee provider (validated in the FormRequest via
     * ExistingEmployeeId).
     */
    private function fillEmployeeFromProvider(array $data): array
    {
        if (empty($data['employee_id'])) {
            $data['employee_id'] = null;
            $data['employee_name'] = null;
            $data['division'] = null;
            $data['department'] = null;

            return $data;
        }

        $employee = $this->employeeRepo->findById($data['employee_id']);

        if (!$employee) {
            abort(422, 'Employee ID tidak dikenal.');
        }

        $data['employee_name'] = $employee->fullName;
        $data['division']      = $employee->division;
        $data['department']    = $employee->department;

        return $data;
    }

    private function normalizeClassificationFields(array $data): array
    {
        if (in_array($data['cert_type'], [\App\Enums\CertType::ISO->value, \App\Enums\CertType::K3->value], true)) {
            $data['product_scope'] = null;
            $data['brand'] = null;
        } else {
            $data['company_scope'] = null;
        }

        return $data;
    }
}
