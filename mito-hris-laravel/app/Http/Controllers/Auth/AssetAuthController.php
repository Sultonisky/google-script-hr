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
 * Reuses the existing session-based Users authentication (AuthService) and the
 * shared `auth.login` view. Authorization is portal-scoped: only users holding
 * the `view_asset` gate may obtain an hr_user session on the Asset domain, and
 * only Asset routes are reachable there. No second user table, guard, or
 * permission system is introduced — RBAC/Gates remain the authority.
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
        if (session()->has('hr_user')) {
            return redirect()->route('assets.portal.index');
        }

        return view('auth.login', [
            'loginPostUrl'         => route('assets.login.post'),
            'loginRedirectDefault' => route('assets.portal.index'),
            'loginTitle'           => 'Portal Aset',
            'loginSubtitle'        => 'Masuk untuk mengelola aset perusahaan',
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

        // Portal-scoped gate: the User sheet account must hold view_asset to use
        // the Asset portal. Without it, no session is created on this domain.
        if (!Gate::forUser($result['user'])->allows('view_asset')) {
            return $this->loginFailure($request, 'Akun Anda tidak memiliki akses ke Portal Aset.');
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

        return redirect()->route('assets.login')->with('info', 'Anda telah berhasil keluar dari Portal Aset.');
    }

    private function loginSuccess(Request $request, array $user, bool $remember): RedirectResponse|JsonResponse
    {
        $request->session()->regenerate();

        // Forget any stale MPR session so portals remain isolated.
        $request->session()->forget(config('mpr.session_key', 'mpr_requestor_auth'));
        $request->session()->forget('hris_remember');

        $user['portal'] = 'assets';
        $request->session()->put('hr_user', $user);
        $this->auditRepo->log('Authentication', $user['email'] ?? $user['username'] ?? 'UNKNOWN', 'logged_in', null, null, null, $user['email'] ?? 'UNKNOWN', 'Authentication');

        if ($remember) {
            $request->session()->put('hris_remember', true);
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
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $errorMessage,
            ], 422);
        }

        return back()->withErrors(['identifier' => $errorMessage]);
    }
}
