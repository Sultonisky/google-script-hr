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
 * Dedicated Asset Portal authentication.
 *
 * Reuses the existing session-based Users authentication (AuthService). The
 * session context is portal-specific; the login view is the ONE shared HRIS
 * login (auth.login) for visual parity with hrismitogroup.web.id/login.
 * Authorization is portal-scoped: only users holding
 * the `access_assets_portal` gate may obtain an asset_auth session on the
 * Asset domain, and only Asset routes are reachable there. No second user
 * table, guard, or permission system is introduced — RBAC/Gates remain the
 * authority.
 */
class AssetAuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function portal(): RedirectResponse
    {
        return redirect()->route('assets.portal.index');
    }

    public function showLoginForm(): View|RedirectResponse
    {
        if (session()->has('asset_auth')) {
            return redirect()->route('assets.portal.index');
        }

        // Reuse the ONE shared HRIS login view (visual source of truth) so the
        // Asset portal login is visually identical to hrismitogroup.web.id/login.
        // Only the portal context differs; authentication behavior is unchanged.
        return view('auth.login', [
            'loginPostUrl'         => route('assets.login.post'),
            'loginRedirectDefault' => route('assets.portal.index'),
            'loginTitle'           => 'Portal Aset',
            'loginSubtitle'        => 'Masuk untuk mengelola aset perusahaan.',
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

        // Portal-scoped gate: the User sheet account must hold access_assets_portal
        // to use the Asset portal. Without it, no session is created on this domain.
        if (!Gate::forUser($result['user'])->allows('access_assets_portal')) {
            return $this->authorizationDenied($request);
        }

        return $this->loginSuccess($request, $result['user'], $data['rememberMe'] ?? false);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = session('asset_auth', []);
        $this->auditRepo->log('Authentication', $user['email'] ?? 'UNKNOWN', 'logged_out', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');
        $request->session()->forget(['asset_auth', 'assets_remember']);
        $request->session()->regenerateToken();

        return redirect()->route('assets.login')->with('info', 'Anda telah berhasil keluar dari Portal Aset.');
    }

    private function loginSuccess(Request $request, array $user, bool $remember): RedirectResponse|JsonResponse
    {
        $request->session()->regenerate();

        $user['portal'] = 'assets';
        $user['auth_domain'] = 'assets';
        $request->session()->put('asset_auth', $user);
        $this->auditRepo->log('Authentication', $user['email'] ?? $user['username'] ?? 'UNKNOWN', 'logged_in', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');

        if ($remember) {
            $request->session()->put('assets_remember', true);
        }

        $redirect = route('assets.portal.index');

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
            return response()->json(['success' => false, 'error' => 'Anda tidak memiliki akses ke Portal Aset.'], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke Portal Aset.');
    }
}
