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
 * Mirrors AssetAuthController: reuses AuthService with a dedicated portal
 * view and session, plus portal-scoped authorization via the `certificates.access` gate.
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

        return view('certificates.auth.login');
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
        $fullName = trim((string) ($user['Full Name'] ?? $user['fullName'] ?? $user['username'] ?? 'User'));
        $greeting = "Selamat datang, {$fullName}! Anda telah berhasil masuk ke Portal Sertifikasi.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user'    => $user,
                'redirect' => $redirect,
                'message' => $greeting,
            ]);
        }

        return redirect($redirect)->with('success', $greeting);
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
        $message = 'Anda tidak memiliki akses ke Certificates Portal. Akun Anda belum diberikan izin untuk mengakses portal ini. Silakan hubungi administrator jika Anda membutuhkan akses.';

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
