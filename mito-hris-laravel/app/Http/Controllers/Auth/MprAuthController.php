<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MprAuthController extends Controller
{
    public function __construct(
        protected MprRequestorRepositoryInterface $requestorRepo,
    ) {}

    public function portal(): View
    {
        return view('auth.mpr-portal');
    }

    public function showLoginForm(): View|RedirectResponse
    {
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $user = session($sessionKey, []);

        if (empty($user)) {
            $fallbackUser = session('hr_user', []);
            $role = strtolower(trim((string) ($fallbackUser['role'] ?? '')));
            $authDomain = strtolower(trim((string) ($fallbackUser['auth_domain'] ?? '')));

            if ($authDomain === 'mpr_requestor' || $role === 'manpower') {
                $user = $fallbackUser;
            }
        }

        if (!empty($user) && (($user['auth_domain'] ?? '') === 'mpr_requestor' || strtolower(trim((string) ($user['role'] ?? ''))) === 'manpower')) {
            return redirect()->route('mpr.auth.request');
        }

        return view('auth.mpr-auth');
    }

    protected function normalizeJobPosition(array $requestor): string
    {
        foreach (['Job Position', 'jobPosition', 'job_position'] as $key) {
            $value = trim((string) ($requestor[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = strtolower(trim((string) $request->input('identifier')));
        $password = (string) $request->input('password');
        $key = 'mpr-login|' . $identifier . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak percobaan. Silakan coba lagi dalam ' . $seconds . ' detik.'],
            ]);
        }

        RateLimiter::hit($key);

        $requestor = $this->requestorRepo->findByIdentifier($identifier);

        if (!$requestor || strtolower(trim((string) ($requestor['Status'] ?? ''))) !== 'active') {
            return redirect()->route('mpr.auth.login')->with('error', 'Akun tidak memiliki akses ke MPR.');
        }

        $storedHash = trim((string) ($requestor['Password Hash'] ?? ''));
        $passwordValid = false;

        if ($storedHash !== '') {
            if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$2b$')) {
                $passwordValid = password_verify($password, $storedHash);
            } else {
                $passwordValid = hash_equals($storedHash, hash('sha256', $password));
            }
        }

        if (!$passwordValid) {
            return redirect()->route('mpr.auth.login')->with('error', 'Password salah. Silakan coba lagi.');
        }

        $request->session()->regenerate();
        $request->session()->put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email' => strtolower(trim((string) ($requestor['Email'] ?? $identifier))),
            'fullName' => trim((string) ($requestor['Full Name'] ?? $requestor['Email'] ?? 'Requestor')),
            'jobPosition' => $this->normalizeJobPosition($requestor),
            'role' => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal' => 'mpr',
            'requestor_id' => trim((string) ($requestor['Requestor ID'] ?? '')),
        ]);

        return redirect()->route('mpr.auth.request');
    }

    public function logout(Request $request)
    {
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $request->session()->forget($sessionKey);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mpr.auth.login');
    }
}
