<?php

namespace App\Http\Controllers\Public;

use App\DTOs\OutsourceEmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\OutsourceApplyRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\AttendanceOutsourcePushService;
use App\Services\RecruitmentService;
use App\Support\OutsourceEmployeeFilter;
use App\Support\PublicConsentEvidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class OutsourceApplyController extends Controller
{
    private const SUBMISSION_COMPLETED = 'public_outsource_submission_completed';
    private const CONTACT_DUPLICATE_MESSAGE = 'Nomor WhatsApp atau email ini sudah terdaftar. Setiap nomor WhatsApp dan email hanya dapat digunakan untuk satu kali pendaftaran.';

    public function __construct(
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        if (session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.success');
        }

        return view('public.outsource.apply');
    }

    /**
     * Probe whether a WhatsApp number / email is already registered (does not create a lock).
     */
    public function checkContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->contactRegistrationStatus(
            $this->canonicalPhone($validated['whatsapp_number'] ?? null),
            $validated['email'] ?? null,
        ));
    }

    public function success(): View|RedirectResponse
    {
        if (!session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.apply');
        }

        return view('public.career.success', [
            'candidate' => null,
            'id' => null,
            'isOutsource' => true,
        ]);
    }

    public function store(OutsourceApplyRequest $request, AttendanceOutsourcePushService $attendancePush): RedirectResponse
    {
        if (session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.success');
        }

        $validated = $request->validated();
        $phone = $this->canonicalPhone($validated['whatsapp_number']);
        $email = strtolower(trim((string) $validated['email']));
        $lockKeys = $this->contactLockKeys($phone, $email);
        $acquired = [];

        try {
            foreach ($lockKeys as $key) {
                if (!Cache::add($key, 1, 90)) {
                    $this->releaseLocks($acquired);

                    return back()->withInput()->with('error', self::CONTACT_DUPLICATE_MESSAGE);
                }
                $acquired[] = $key;
            }

            if ($this->outsourceRepo->findByContact($phone, $email) !== null) {
                $this->releaseLocks($acquired);

                return back()->withInput()->with('error', self::CONTACT_DUPLICATE_MESSAGE);
            }

            $consentEvidence = PublicConsentEvidence::capture($request, 'outsource_agreement');

            $created = $this->outsourceRepo->create(new OutsourceEmployeeData(
                fullName: $validated['full_name'],
                citizenIdAddress: $validated['citizen_id_address'],
                birthDate: $validated['birth_date'],
                birthPlace: $validated['birth_place'],
                lastEducation: $validated['last_education'],
                whatsappNumber: $phone,
                email: $email,
                jobTitle: $validated['job_title'],
                workLocation: $validated['work_location'],
                workCity: $validated['work_city'],
                bankAccount: null,
                mitoJoinDate: $validated['mito_join_date'] ?? null,
                contractStartDate: $validated['contract_start_date'] ?? null,
                contractEndDate: $validated['contract_end_date'] ?? null,
                costCenter: $validated['cost_center'],
                entity: $validated['entity'],
                payrollScheme: null,
                umkAmount: null,
                vendor: $validated['vendor'],
                createdBy: 'Portal Outsource',
            ));

            try {
                $this->auditRepo->log(
                    entityType: 'Outsource',
                    entityId: (string) $created->outsourceId,
                    action: 'created',
                    field: 'Outsource ID',
                    oldValue: '-',
                    newValue: [
                        'outsource_id' => $created->outsourceId,
                        'vendor' => $validated['vendor'],
                        'agreement_evidence' => $consentEvidence,
                    ],
                    user: 'Public Applicant',
                    source: 'Public'
                );
            } catch (\Throwable $auditError) {
                report($auditError);
            }

            $attendancePush->pushCreated([$created]);

            session([self::SUBMISSION_COMPLETED => true]);

            return redirect()->route('public.outsource.success');
        } catch (\Throwable $e) {
            $this->releaseLocks($acquired);
            report($e);

            return back()->withInput()->with('error', 'Pendaftaran gagal disimpan. Silakan coba lagi beberapa saat lagi.');
        }
    }

    /**
     * @return array{available: bool, message: ?string, duplicate_fields: list<string>}
     */
    public function contactRegistrationStatus(?string $phone, ?string $email): array
    {
        $email = strtolower(trim((string) $email));
        $duplicateFields = [];

        if ($phone !== null && $phone !== '') {
            $phoneLockKey = $this->contactLockKeys($phone, null)[0] ?? null;
            if (($phoneLockKey !== null && Cache::has($phoneLockKey))
                || $this->outsourceRepo->findByContact($phone, null) !== null) {
                $duplicateFields[] = 'whatsapp_number';
            }
        }

        if ($email !== '') {
            $emailLockKey = $this->contactLockKeys(null, $email)[0] ?? null;
            if (($emailLockKey !== null && Cache::has($emailLockKey))
                || $this->outsourceRepo->findByContact(null, $email) !== null) {
                $duplicateFields[] = 'email';
            }
        }

        $available = $duplicateFields === [];

        return [
            'available' => $available,
            'message' => $available ? null : self::CONTACT_DUPLICATE_MESSAGE,
            'duplicate_fields' => $duplicateFields,
        ];
    }

    private function canonicalPhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

        return $digits === '' ? null : RecruitmentService::normalizePhone($digits);
    }

    /**
     * @return list<string>
     */
    private function contactLockKeys(?string $phone, ?string $email): array
    {
        $keys = [];
        $phoneKey = OutsourceEmployeeFilter::phoneKey($phone);
        if ($phoneKey !== '') {
            $keys[] = 'outsource-apply-phone:' . $phoneKey;
        }
        $email = strtolower(trim((string) $email));
        if ($email !== '') {
            $keys[] = 'outsource-apply-email:' . sha1($email);
        }

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     */
    private function releaseLocks(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
