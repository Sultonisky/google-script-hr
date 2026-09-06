<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dedicated Certificate Portal authentication.
 *
 * Mirrors AssetAuthController: reuses AuthService and the shared `auth.login`
 * view, with portal-scoped authorization via the `view_certification` gate.
 */
class CertificateAuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function portal(): RedirectResponse
    {
        return redirect()->route('certificates.portal.index');
    }

    public function showLoginForm(): View|RedirectResponse
    {
        if (session()->has('hr_user')) {
            return redirect()->route('certificates.portal.index');
        }

        return view('auth.login', [
            'loginPostUrl'         => route('certificates.login.post'),
            'loginRedirectDefault' => route('certificates.portal.index'),
            'loginTitle'           => 'Portal Sertifikasi',
            'loginSubtitle'        => 'Masuk untuk mengelola sertifikasi karyawan',
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $data       = $request->validated();
        $identifier = strtolower(trim((string) ($data['identifier'] ?? '')));
        $password   = (string) ($data['password'] ?? '');

        $result = $this->authService->attemptLogin($identifier, $password);

        if (!($result['success'] ?? false)) {
            return $this->loginFailure($request, $result['error'] ?? 'Login gagal.');
        }

        if (!Gate::forUser($result['user'])->allows('view_certification')) {
            return $this->loginFailure($request, 'Akun Anda tidak memiliki akses ke Portal Sertifikasi.');
        }

        return $this->loginSuccess($request, $result['user'], $data['rememberMe'] ?? false);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = session('hr_user', []);
        $this->auditRepo->log('Authentication', $user['email'] ?? 'UNKNOWN', 'logged_out', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');
        session()->forget('hr_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('certificates.login')->with('info', 'Anda telah berhasil keluar dari Portal Sertifikasi.');
    }

    private function loginSuccess(Request $request, array $user, bool $remember): RedirectResponse|JsonResponse
    {
        $request->session()->regenerate();

        $request->session()->forget(config('mpr.session_key', 'mpr_requestor_auth'));
        $request->session()->forget('hris_remember');

        $user['portal'] = 'certificates';
        $request->session()->put('hr_user', $user);
        $this->auditRepo->log('Authentication', $user['email'] ?? $user['username'] ?? 'UNKNOWN', 'logged_in', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');

        if ($remember) {
            $request->session()->put('hris_remember', true);
        }

        $redirect = route('certificates.portal.index');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user'    => $user,
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect);
    }

    private function loginFailure(Request $request, string $errorMessage): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $errorMessage,
            ], 422);
        }

        return back()->withErrors(['identifier' => $errorMessage]);
    }
}
