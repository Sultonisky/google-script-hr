<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Services\PermissionResolver;
use App\Support\Rbac;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected PermissionResolver $permissionResolver,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    /**
     * Show the Internal HRIS Portal Landing Page (Gateway before login).
     */
    public function portal(): View
    {
        return view('auth.portal');
    }

    /**
     * Show the HR login form.
     * If already authenticated, redirect to HRIS dashboard.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (session()->has('hr_user')) {
            return redirect($this->postLoginRedirect(session('hr_user')));
        }

        return view('auth.login');
    }

    /**
     * Handle HRIS authentication — internal Users sheet only.
     *
     * Authenticates against the internal Users sheet via AuthService.
     * No fallback to MPR Requestors sheet.
     * No cross-domain authentication.
     */
    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $data       = $request->validated();
        $identifier = strtolower(trim((string) ($data['identifier'] ?? '')));
        $password   = (string) ($data['password'] ?? '');

        // HRIS authentication only — Users Sheet
        $result = $this->authService->attemptLogin($identifier, $password);

        if (!($result['success'] ?? false)) {
            return $this->loginFailure(
                $request,
                $result['error'] ?? 'Login gagal.'
            );
        }

        if (!$this->permissionResolver->hasHrisAccess($result['user'])) {
            return $this->portalAccessDenied($request);
        }

        return $this->loginSuccess(
            $request,
            $result['user'],
            $data['rememberMe'] ?? false
        );
    }

    /**
     * Log out the authenticated HRIS user.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = session('hr_user', []);
        $this->auditRepo->log('Authentication', $user['email'] ?? 'UNKNOWN', 'logged_out', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');
        session()->forget('hr_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Seed a Super Admin if no users exist yet (first-run helper).
     */
    public function seedSuperAdmin(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'fullName' => ['required', 'string', 'max:255'],
        ]);

        $email    = strtolower(trim((string) $request->input('email')));
        $fullName = trim((string) $request->input('fullName'));

        $result = $this->authService->seedSuperAdmin($email, $fullName);

        if (!($result['success'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Gagal membuat Super Admin.',
                ], 422);
            }
            return back()->withErrors(['email' => $result['error'] ?? 'Gagal membuat Super Admin.']);
        }

        $request->session()->put('hr_user', $result['user']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user'    => $result['user'],
            ]);
        }

        return redirect()->route('hr.dashboard');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function loginSuccess(
        Request $request,
        array $user,
        bool $remember
    ): RedirectResponse|JsonResponse {
        $request->session()->regenerate();

        // Remove any stale MPR session so that HRIS and MPR authentication contexts
        // remain strictly isolated. Both portals share the same PHP session storage
        // (shared cookie domain), so without this forget() the mpr_requestor_auth key
        // would survive into the HRIS session and leak MPR identity into HRIS views.
        $request->session()->forget(config('mpr.session_key', 'mpr_requestor_auth'));

        $request->session()->forget('hris_remember');
        $user['portal'] = $user['portal'] ?? 'hris';
        $request->session()->put('hr_user', $user);
        $this->auditRepo->log('Authentication', $user['email'] ?? $user['username'] ?? 'UNKNOWN', 'logged_in', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');

        if ($remember) {
            $request->session()->put('hris_remember', true);
        }

        // HRIS authentication always remains inside the HRIS portal. Dedicated
        // Asset and Certificate portals have their own login boundaries.
        $redirect = $this->postLoginRedirect($user);
        $fullName = trim((string) ($user['Full Name'] ?? $user['fullName'] ?? $user['username'] ?? 'User'));
        $greeting = "Selamat datang, {$fullName}! Anda telah berhasil masuk ke Sistem HRIS.";

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'user'     => $user,
                'redirect' => $redirect,
                'message'  => $greeting,
            ]);
        }

        return redirect($redirect)->with('success', $greeting);
    }


    /**
     * Decide the post-login landing route from the authenticated user's role.
     *
    * All HRIS users remain in the HRIS dashboard after HRIS authentication.
     */
    private function postLoginRedirect(array $user): string
    {
        $role = Rbac::normalizeRole($user['role'] ?? null);

        return route('hr.dashboard');
    }

    private function loginFailure(
        Request $request,
        string $errorMessage
    ): RedirectResponse|JsonResponse {
        $genericError = 'Email/username atau password salah.';
        $this->auditRepo->log('Authentication', $request->input('identifier', 'UNKNOWN'), 'login_failed', null, null, null, $request->input('identifier', 'UNKNOWN'), 'Authentication');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $genericError,
            ], 422);
        }

        throw ValidationException::withMessages([
            'identifier' => [$genericError],
        ]);
    }

    private function portalAccessDenied(Request $request): RedirectResponse|JsonResponse
    {
        $message = 'Anda tidak memiliki akses ke HRIS Portal. Akun Anda belum diberikan izin untuk mengakses portal ini. Silakan hubungi administrator jika Anda membutuhkan akses.';
        $this->auditRepo->log('Authentication', $request->input('identifier', 'UNKNOWN'), 'portal_access_denied', null, null, null, $request->input('identifier', 'UNKNOWN'), 'Authentication');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $message,
                'code'    => 'PORTAL_ACCESS_DENIED',
            ], 403);
        }

        abort(403, $message);
    }
}
