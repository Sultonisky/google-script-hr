<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        $users = Cache::get('hr_system_users', [
            [
                'id'         => 1,
                'name'       => 'HR Administrator',
                'email'      => 'admin@mitocareer.com',
                'role'       => 'Super Admin',
                'status'     => 'Aktif',
                'last_login' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ],
            [
                'id'         => 2,
                'name'       => 'HR Recruiter Team',
                'email'      => 'recruitment@mitocareer.com',
                'role'       => 'Super User',
                'status'     => 'Aktif',
                'last_login' => now()->timezone('Asia/Jakarta')->subHours(2)->format('Y-m-d H:i:s'),
            ]
        ]);

        return view('hr.users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|min:3',
            'email' => 'required|email',
            'role'  => ['required', 'string', Rule::in(config('hris.auth.valid_roles_internal', []))],
        ]);

        $users = Cache::get('hr_system_users', []);
        $users[] = [
            'id'         => count($users) + 1,
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'status'     => 'Aktif',
            'last_login' => '-',
        ];

        Cache::forever('hr_system_users', $users);

        return redirect()->route('hr.users.index')->with('success', "Pengguna '{$validated['name']}' berhasil ditambahkan.");
    }
}
