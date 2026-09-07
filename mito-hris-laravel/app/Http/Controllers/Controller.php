<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Resolve the authenticated HR user's display name from the HRIS session.
     *
     * The HRIS portal uses a custom session key 'hr_user' (not Laravel's built-in
     * Auth facade). Auth::user() always returns null in this application.
     *
     * @param  string  $fallback
     * @return string
     */
    protected function hrUserName(string $fallback = 'HR Administrator'): string
    {
        return session('hr_user.fullName')
            ?? session('hr_user.email')
            ?? $fallback;
    }
}
