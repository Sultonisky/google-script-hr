<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = Cache::get('portal_settings', [
            'company_name'     => 'PT MITO ELECTRONIC INDONESIA',
            'portal_title'     => 'MITO HRIS',
            'portal_subtitle'  => 'Applicant Tracking System & HR Portal',
            'theme_color'      => '#eb1c24',
            'company_address'  => 'Jl. Pluit Raya No. 19, Penjaringan, Jakarta Utara',
            'company_email'    => 'hrd@mito.co.id',
            'auto_refresh'     => false,
            'refresh_interval' => 30,
        ]);

        return view('hr.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'     => 'required|string',
            'portal_title'     => 'required|string',
            'portal_subtitle'  => 'nullable|string',
            'theme_color'      => 'nullable|string',
            'company_address'  => 'nullable|string',
            'company_email'    => 'nullable|email',
        ]);

        Cache::forever('portal_settings', array_merge(
            Cache::get('portal_settings', []),
            $validated
        ));

        return redirect()->route('hr.settings.index')->with('success', 'Pengaturan portal berhasil disimpan.');
    }
}
