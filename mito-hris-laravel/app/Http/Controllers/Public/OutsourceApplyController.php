<?php

namespace App\Http\Controllers\Public;

use App\DTOs\OutsourceEmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\OutsourceApplyRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\RecruitmentService;
use App\Support\OutsourceEmployeeFilter;
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

    public function store(OutsourceApplyRequest $request): RedirectResponse
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

            $consentEvidence = [
                'stage' => 'outsource_agreement',
                'accepted' => true,
                'server_timestamp' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'device' => substr((string) $request->input('consent_device', $request->input('device')), 0, 120),
                'client_timestamp' => $request->input('consent_timestamp', $request->input('client_timestamp')),
                'latitude' => $request->input('consent_latitude', $request->input('latitude')),
                'longitude' => $request->input('consent_longitude', $request->input('longitude')),
                'location' => $request->input('consent_location', $request->input('location')),
            ];

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
                bankAccount: $validated['bank_account'],
                mitoJoinDate: $validated['mito_join_date'],
                contractStartDate: $validated['contract_start_date'],
                contractEndDate: $validated['contract_end_date'],
                costCenter: $validated['cost_center'],
                entity: $validated['entity'],
                payrollScheme: $validated['payroll_scheme'],
                umkAmount: (float) $validated['umk_amount'],
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

            session([self::SUBMISSION_COMPLETED => true]);

            return redirect()->route('public.outsource.success');
        } catch (\Throwable $e) {
            $this->releaseLocks($acquired);
            report($e);

            return back()->withInput()->with('error', 'Pendaftaran gagal disimpan. Silakan coba lagi beberapa saat lagi.');
        }
    }

    /**
     * @return array{available: bool, message: ?string}
     */
    public function contactRegistrationStatus(?string $phone, ?string $email): array
    {
        $email = strtolower(trim((string) $email));
        if (($phone ?? '') === '' && $email === '') {
            return ['available' => true, 'message' => null];
        }

        foreach ($this->contactLockKeys($phone, $email) as $key) {
            if (Cache::has($key)) {
                return ['available' => false, 'message' => self::CONTACT_DUPLICATE_MESSAGE];
            }
        }

        if ($this->outsourceRepo->findByContact($phone, $email) !== null) {
            return ['available' => false, 'message' => self::CONTACT_DUPLICATE_MESSAGE];
        }

        return ['available' => true, 'message' => null];
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
