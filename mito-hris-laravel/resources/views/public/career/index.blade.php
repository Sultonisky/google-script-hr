@extends('layouts.public')

@section('title', 'Pendaftaran Karir - PT MITO')

@section('content')
<!-- Hero Section (1:1 from GAS FormPendaftaran.html) -->
<div class="hero-section text-white mb-0">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <div class="hero-org">PT MITO ELECTRONIC INDONESIA</div>
        <div class="hero-dept">HUMAN CAPITAL & RECRUITMENT</div>
        <h1 class="hero-title fw-bold">Portal Pendaftaran Karir</h1>
        <p class="hero-subtitle mb-0">Bergabunglah bersama keluarga besar MITO. Silakan lengkapi formulir di bawah ini dengan data yang valid dan lengkap.</p>
      </div>
      <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0 d-none d-md-block">
        <div class="rounded-circle bg-white text-danger d-inline-flex align-items-center justify-content-center shadow-lg" style="width: 130px; height: 130px;">
          <h2 class="fw-bold mb-0">MITO</h2>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sticky Progress Section -->
<div class="progress-section mb-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="progress-label fw-bold">Progres Kelengkapan Formulir</span>
      <span class="progress-message fw-bold" id="progressPercent">0%</span>
    </div>
    <div class="progress-bar-wrap">
      <div class="progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
    </div>
  </div>
</div>

<div class="content-wrap py-2">
  <div class="container">
    <form action="{{ route('public.career.store') }}" method="POST" id="formPendaftaran" novalidate>
      @csrf

      <!-- SECTION 1: PERSONAL INFORMATION -->
      <div class="form-section" id="sectionPersonal">
        <div class="form-section-header">
          <i class="bi bi-person-fill"></i>
          <div>
            <h5>Informasi Pribadi</h5>
            <p>Data identitas dan informasi personal Anda sesuai KTP</p>
          </div>
        </div>
        <div class="form-section-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="nik">NIK (Nomor Induk Kependudukan) <span class="required text-danger">*</span></label>
              <input type="text" class="form-control" id="nik" name="nik" required maxlength="16" pattern="[0-9]{16}" autocomplete="off" inputmode="numeric" placeholder="16 digit NIK KTP">
              <div class="nik-info">
                <i class="bi bi-info-circle-fill"></i>
                <div>Sistem mendeteksi Provinsi, Kota, Jenis Kelamin, dan Tanggal Lahir otomatis saat Anda mengetik 16 digit NIK.</div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="full_name">Nama Lengkap <span class="required text-danger">*</span></label>
              <input type="text" class="form-control" id="full_name" name="nama_lengkap" required minlength="3" autocomplete="name" placeholder="Nama sesuai KTP">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="birth_date">Tanggal Lahir <span class="required text-danger">*</span></label>
              <input type="date" class="form-control" id="birth_date" name="tanggal_lahir" required>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="age">Usia (Tahun)</label>
              <input type="number" class="form-control" id="age" name="usia" readonly placeholder="Otomatis dari Tgl Lahir">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="gender">Jenis Kelamin <span class="required text-danger">*</span></label>
              <select class="form-select" id="gender" name="jenis_kelamin" required>
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="marital_status">Status Pernikahan</label>
              <select class="form-select" id="marital_status" name="marital_status">
                <option value="">-- Pilih Status --</option>
                <option value="Belum Menikah">Belum Menikah</option>
                <option value="Menikah">Menikah</option>
                <option value="Cerai">Cerai</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2: CONTACT INFORMATION -->
      <div class="form-section" id="sectionContact">
        <div class="form-section-header">
          <i class="bi bi-envelope-fill"></i>
          <div>
            <h5>Informasi Kontak</h5>
            <p>Data komunikasi dan domisili Anda</p>
          </div>
        </div>
        <div class="form-section-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="email">Alamat Email Aktif <span class="required text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" required placeholder="nama@email.com">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Nomor HP / WhatsApp <span class="required text-danger">*</span></label>
              <div class="input-group-prefix">
                <span class="input-prefix">+62</span>
                <input type="tel" class="form-control" id="phone" name="no_telp" required placeholder="81234567890">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="province">Provinsi KTP</label>
              <select class="form-select" id="province" name="provinsi"><option value="">-- Pilih Provinsi --</option></select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="city">Kota / Kabupaten KTP</label>
              <select class="form-select" id="city" name="kota"><option value="">-- Pilih Kota --</option></select>
            </div>
            <div class="col-12">
              <label class="form-label" for="address">Detail Alamat KTP</label>
              <textarea class="form-control" id="address" name="alamat" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, dll."></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 3: EMPLOYMENT & QUALIFICATIONS -->
      <div class="form-section" id="sectionEmployment">
        <div class="form-section-header">
          <i class="bi bi-briefcase-fill"></i>
          <div>
            <h5>Informasi Kualifikasi & Pekerjaan</h5>
            <p>Posisi yang dilamar dan latar belakang pengalaman</p>
          </div>
        </div>
        <div class="form-section-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="position_applied">Posisi yang Dilamar <span class="required text-danger">*</span></label>
              <select class="form-select" id="position_applied" name="posisi" required>
                <option value="">-- Pilih Posisi --</option>
                <option value="Staff HRD">Staff HRD</option>
                <option value="Accounting & Tax Staff">Accounting & Tax Staff</option>
                <option value="Sales Executive">Sales Executive</option>
                <option value="Digital Marketing">Digital Marketing</option>
                <option value="Customer Service">Customer Service</option>
                <option value="Warehouse Staff">Warehouse Staff</option>
                <option value="Graphic Designer">Graphic Designer</option>
                <option value="IT Support & Developer">IT Support & Developer</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="education">Pendidikan Terakhir</label>
              <select class="form-select" id="education" name="pendidikan">
                <option value="">-- Pilih Pendidikan --</option>
                <option value="SMA/SMK">SMA/SMK</option>
                <option value="D3">D3</option>
                <option value="S1">S1</option>
                <option value="S2">S2</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="work_experience">Pengalaman Kerja</label>
              <select class="form-select" id="work_experience" name="pengalaman_kerja">
                <option value="">-- Pilih Pengalaman --</option>
                <option value="Fresh Graduate">Fresh Graduate</option>
                <option value="1-2 Tahun">1-2 Tahun</option>
                <option value="3-5 Tahun">3-5 Tahun</option>
                <option value="Lebih dari 5 Tahun">Lebih dari 5 Tahun</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="last_company">Perusahaan Terakhir</label>
              <input type="text" class="form-control" id="last_company" name="perusahaan_terakhir" placeholder="Nama perusahaan sebelumnya (opsional)">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="available_to_join">Ketersediaan Bergabung</label>
              <select class="form-select" id="available_to_join" name="ketersediaan_bergabung">
                <option value="Segera">Segera (Immediately)</option>
                <option value="1 Minggu">1 Minggu</option>
                <option value="2 Minggu">2 Minggu</option>
                <option value="1 Bulan">1 Bulan</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="expected_salary">Ekspektasi Gaji</label>
              <div class="input-group-prefix">
                <span class="input-prefix">Rp</span>
                <input type="text" class="form-control" id="expected_salary" name="gaji_yang_diharapkan" placeholder="Contoh: 5.000.000">
              </div>
            </div>
            <div class="col-md-12">
              <label class="form-label" for="cv_link">Tautan / Link CV & Portofolio (Google Drive / LinkedIn / Web)</label>
              <input type="url" class="form-control" id="cv_link" name="cv_link" placeholder="https://drive.google.com/... atau https://linkedin.com/in/...">
              <small class="text-muted">Pastikan link dapat diakses publik atau telah diberikan izin baca.</small>
            </div>
          </div>
        </div>
      </div>

      <!-- AGREEMENT DECLARATION -->
      <div class="agreement-section">
        <div class="agreement-checkbox">
          <input class="form-check-input" type="checkbox" id="agreement" required>
          <label class="form-check-label" for="agreement">
            Saya menyatakan bahwa seluruh data yang saya isi adalah benar dan dapat dipertanggungjawabkan. Saya bersedia mengikuti seluruh proses rekrutmen PT MITO ELECTRONIC INDONESIA sesuai prosedur yang berlaku.
          </label>
        </div>
      </div>

      <!-- SUBMIT BUTTON -->
      <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn-submit" id="btnSubmit">
          <i class="bi bi-send me-2"></i> Kirim Lamaran Pekerjaan
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    // Load Provinces
    const provSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');

    try {
      const res = await fetch('/api/v1/provinces');
      const provinces = await res.json();
      for (const [code, name] of Object.entries(provinces)) {
        const opt = document.createElement('option');
        opt.value = code;
        opt.textContent = name;
        provSelect.appendChild(opt);
      }
    } catch (e) {
      console.error('Error fetching provinces:', e);
    }

    provSelect.addEventListener('change', async () => {
      citySelect.innerHTML = '<option value="">-- Pilih Kota --</option>';
      const code = provSelect.value;
      if (!code) return;

      try {
        const res = await fetch(`/api/v1/cities/${code}`);
        const cities = await res.json();
        for (const [cCode, cName] of Object.entries(cities)) {
          const opt = document.createElement('option');
          opt.value = cName;
          opt.textContent = cName;
          citySelect.appendChild(opt);
        }
      } catch (e) {
        console.error('Error fetching cities:', e);
      }
    });

    // Auto calculate age
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('age');
    birthDateInput.addEventListener('change', () => {
      if (birthDateInput.value) {
        const birthDate = new Date(birthDateInput.value);
        const diff = Date.now() - birthDate.getTime();
        const ageDate = new Date(diff);
        const age = Math.abs(ageDate.getUTCFullYear() - 1970);
        ageInput.value = isNaN(age) ? '' : age;
      }
    });

    // Simple progress calculator
    const form = document.getElementById('formPendaftaran');
    const progressFill = document.getElementById('progressBarFill');
    const progressText = document.getElementById('progressPercent');

    form.addEventListener('input', () => {
      const requiredInputs = form.querySelectorAll('[required]');
      let filled = 0;
      requiredInputs.forEach(input => {
        if (input.type === 'checkbox' ? input.checked : input.value.trim() !== '') {
          filled++;
        }
      });
      const percent = Math.min(100, Math.round((filled / requiredInputs.length) * 100));
      progressFill.style.width = percent + '%';
      progressText.textContent = percent + '%';
    });
  });
</script>
@endsection
