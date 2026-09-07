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
 * Mirrors AssetAuthController: reuses AuthService with the shared HRIS login
 * view (auth.login), a dedicated portal session, and portal-scoped
 * authorization via the `access_certificates_portal` gate.
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
        if (session()->has('certificate_auth')) {
            return redirect()->route('certificates.portal.index');
        }

        // Reuse the ONE shared HRIS login view (visual source of truth) so the
        // Certificate portal login is visually identical to
        // hrismitogroup.web.id/login. Only the portal context differs;
        // authentication behavior is unchanged.
        return view('auth.login', [
            'loginPostUrl'         => route('certificates.login.post'),
            'loginRedirectDefault' => route('certificates.portal.index'),
            'loginTitle'           => 'Portal Sertifikasi',
            'loginSubtitle'        => 'Masuk untuk mengelola sertifikasi karyawan.',
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

        if (!Gate::forUser($result['user'])->allows('access_certificates_portal')) {
            return $this->authorizationDenied($request);
        }

        return $this->loginSuccess($request, $result['user'], $data['rememberMe'] ?? false);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = session('certificate_auth', []);
        $this->auditRepo->log('Authentication', $user['email'] ?? 'UNKNOWN', 'logged_out', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');
        $request->session()->forget(['certificate_auth', 'certificates_remember']);
        $request->session()->regenerateToken();

        return redirect()->route('certificates.login')->with('info', 'Anda telah berhasil keluar dari Portal Sertifikasi.');
    }

    private function loginSuccess(Request $request, array $user, bool $remember): RedirectResponse|JsonResponse
    {
        $request->session()->regenerate();

        $user['portal'] = 'certificates';
        $user['auth_domain'] = 'certificates';
        $request->session()->put('certificate_auth', $user);
        $this->auditRepo->log('Authentication', $user['email'] ?? $user['username'] ?? 'UNKNOWN', 'logged_in', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');

        if ($remember) {
            $request->session()->put('certificates_remember', true);
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
        $errorMessage = 'Email/username atau password salah.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $errorMessage,
            ], 422);
        }

        return back()->withErrors(['identifier' => $errorMessage]);
    }

    private function authorizationDenied(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => 'Anda tidak memiliki akses ke Portal Sertifikasi.'], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke Portal Sertifikasi.');
    }
}
