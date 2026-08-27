<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Services\MprRequestorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected MprRequestorAuthService $mprRequestorAuthService,
    ) {}

    /**
     * Show the HR / MPR login form.
     * If already authenticated, redirect to the appropriate area.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (session()->has('hr_user')) {
            $user = session('hr_user');
            if (($user['role'] ?? '') === 'Manpower') {
                return redirect()->route('hr.mpr.index');
            }
            return redirect()->route('hr.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle authentication — dual-domain login flow:
     *
     *   1. Try mpr_requestor sheet first (Manager domain).
     *      - If identifier found → authenticate against mpr_requestor.
     *      - If not found (null error) → proceed to step 2.
     *   2. Try internal Users sheet (HR domain).
     *
     * This guarantees Manager accounts in mpr_requestor are never confused
     * with internal HRIS Users, and vice versa.
     */
    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $data       = $request->validated();
        $identifier = strtolower(trim((string) ($data['identifier'] ?? '')));
        $password   = (string) ($data['password'] ?? '');

        // ------------------------------------------------------------------
        // STEP 1: MPR Requestor domain (Manager)
        // ------------------------------------------------------------------
        $mprResult = $this->mprRequestorAuthService->attemptLogin($identifier, $password);

        if ($mprResult['success'] ?? false) {
            return $this->loginSuccess($request, $mprResult['user'], $data['rememberMe'] ?? false);
        }

        // null error = identifier not found in mpr_requestor → fall through to Users
        // non-null error = identifier found but auth failed → hard rejection
        if (($mprResult['error'] ?? null) !== null) {
            return $this->loginFailure($request, $mprResult['error']);
        }

        // ------------------------------------------------------------------
        // STEP 2: Internal HRIS domain (Users sheet)
        // ------------------------------------------------------------------
        $hrResult = $this->authService->attemptLogin($identifier, $password);

        if (!($hrResult['success'] ?? false)) {
            return $this->loginFailure($request, $hrResult['error'] ?? 'Login gagal.');
        }

        return $this->loginSuccess($request, $hrResult['user'], $data['rememberMe'] ?? false);
    }

    /**
     * Log out the authenticated user (works for both domains).
     */
    public function logout(Request $request): RedirectResponse
    {
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
        $request->session()->put('hr_user', $user);

        if ($remember) {
            $request->session()->put('hris_remember', true);
        }

        $redirect = ($user['role'] ?? '') === 'Manpower'
            ? route('hr.mpr.index')
            : route('hr.dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'user'     => $user,
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect);
    }

    private function loginFailure(
        Request $request,
        string $errorMessage
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $errorMessage,
            ], 422);
        }

        throw ValidationException::withMessages([
            'identifier' => [$errorMessage],
        ]);
    }
}
