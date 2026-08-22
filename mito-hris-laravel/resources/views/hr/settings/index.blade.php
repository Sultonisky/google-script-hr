@extends('layouts.hr')

@section('title', 'Pengaturan - MITO HRIS')
@section('page-title', 'Pengaturan Portal')
@section('page-subtitle', 'Konfigurasi sistem dan parameter global')

@section('content')
<!-- partials/SettingsPanel.html — SETTINGS PAGE SECTION (1:1 from GAS) -->
<section class="page-section active" id="pageSettings">
  <div class="panel settings-card">
    <div class="panel-header"><h6>Pengaturan Portal</h6></div>
    <form action="{{ route('hr.settings.update') }}" method="POST" class="p-3 p-md-4">
      @csrf

      <!-- Portal Info -->
      <div class="settings-group-title"><i class="bi bi-building"></i> Informasi Portal</div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Nama Perusahaan</div>
          <div class="settings-desc">Nama perusahaan yang ditampilkan di seluruh portal.</div>
        </div>
        <input type="text" name="company_name" id="settingCompanyName" value="{{ $settings['company_name'] ?? 'PT MITO ELECTRONIC INDONESIA' }}" required />
      </div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Judul Portal</div>
          <div class="settings-desc">Judul utama di sidebar dan header.</div>
        </div>
        <input type="text" name="portal_title" id="settingPortalTitle" value="{{ $settings['portal_title'] ?? 'MITO HRIS' }}" required />
      </div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Subtitle Portal</div>
          <div class="settings-desc">Deskripsi singkat di bawah judul portal.</div>
        </div>
        <input type="text" name="portal_subtitle" id="settingPortalSubtitle" value="{{ $settings['portal_subtitle'] ?? 'Applicant Tracking System & HR Portal' }}" />
      </div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Warna Tema</div>
          <div class="settings-desc">Warna utama yang digunakan di seluruh portal.</div>
        </div>
        <input type="color" name="theme_color" id="settingThemeColor" value="{{ $settings['theme_color'] ?? '#eb1c24' }}" style="width:60px;height:38px;border:none;cursor:pointer;" />
      </div>

      <!-- Contact -->
      <div class="settings-group-title"><i class="bi bi-envelope"></i> Kontak Perusahaan</div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Alamat</div>
          <div class="settings-desc">Alamat kantor perusahaan.</div>
        </div>
        <input type="text" name="company_address" id="settingCompanyAddress" value="{{ $settings['company_address'] ?? 'Jl. Pluit Raya No. 19, Penjaringan, Jakarta Utara' }}" />
      </div>
      <div class="settings-row">
        <div>
          <div class="settings-label">Email</div>
          <div class="settings-desc">Email resmi HR perusahaan.</div>
        </div>
        <input type="email" name="company_email" id="settingCompanyEmail" value="{{ $settings['company_email'] ?? 'hrd@mito.co.id' }}" />
      </div>

      <!-- Actions -->
      <div class="settings-row">
        <div></div>
        <div class="d-flex gap-2">
          <button class="btn-reset-filter" id="btnResetSettings" type="reset">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Form
          </button>
          <button class="btn-reset-filter" id="btnSaveSettings" type="submit" style="background:var(--color-primary,#eb1c24);color:#fff;">
            <i class="bi bi-check2-circle"></i> Simpan Pengaturan
          </button>
        </div>
      </div>

    </form>
  </div>
</section>
@endsection
