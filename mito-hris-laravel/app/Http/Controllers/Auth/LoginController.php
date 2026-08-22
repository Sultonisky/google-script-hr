<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show the HR login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (session()->has('hr_user')) {
            return redirect()->route('hr.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle HR user authentication against Google Sheets Users sheet.
     */
    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $identifier = strtolower(trim((string) ($data['identifier'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $result = $this->authService->attemptLogin($identifier, $password);

        if (!($result['success'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Login gagal.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'identifier' => [$result['error'] ?? 'Login gagal.'],
            ]);
        }

        $user = $result['user'] ?? [];
        $remember = !empty($data['rememberMe']);

        $request->session()->put('hr_user', $user);

        if ($remember) {
            $request->session()->put('hris_remember', true);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user' => $user,
            ]);
        }

        return redirect()->route('hr.dashboard');
    }

    /**
     * Log out the HR user.
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget('hr_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    public function seedSuperAdmin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'fullName' => ['required', 'string', 'max:255'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $fullName = trim((string) $request->input('fullName'));

        $result = $this->authService->seedSuperAdmin($email, $fullName);

        if (!($result['success'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Gagal membuat Super Admin.',
                ], 422);
            }

            return back()->withErrors(['email' => $result['error'] ?? 'Gagal membuat Super Admin.']);
        }

        $request->session()->put('hr_user', $result['user']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user' => $result['user'],
            ]);
        }

        return redirect()->route('hr.dashboard');
    }
}
