@extends('layouts.public')

@section('title', 'Cek Status Lamaran - MITO Group')

@section('content')
<!-- HERO (1:1 Red MITO Theme) -->
<div class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7 col-md-8">
        <div class="hero-org">MITO Group</div>
        <div class="hero-dept">Human Resources Department</div>
        <h1 class="hero-title">Cek Status Lamaran</h1>
        <p class="hero-subtitle">Pantau progres dan status tahapan seleksi rekrutmen Anda secara langsung dengan memasukkan ID Registrasi atau NIK KTP.</p>
      </div>
      <div class="col-lg-5 col-md-4 d-none d-md-flex justify-content-end align-items-center">
        <img id="heroAvatarImg"
             src="{{ asset('assets/mito.png') }}"
             alt="MITO Official"
             class="hero-avatar-img">
      </div>
    </div>
  </div>
</div>

<div class="content-wrap py-5">
  <div class="container" style="max-width:850px;">

    <!-- Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border:1px solid #e2e8f0!important;">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px; background:#eb1c24;">
            <i class="bi bi-search fs-3"></i>
          </div>
          <h4 class="fw-bold text-navy">Lacak Lamaran Anda</h4>
          <p class="text-muted" style="font-size:13.5px">Masukkan <strong>Nomor Registrasi (REC-...)</strong> atau <strong>16 Digit NIK KTP</strong> yang Anda daftarkan.</p>
        </div>

        <form action="{{ route('public.career.check-status') }}" method="GET" class="row g-2 justify-content-center">
          <div class="col-md-8">
            <input type="text" name="q" class="form-control form-control-lg" style="border-radius:12px;font-size:14px;" placeholder="Contoh: REC-20260818-0001 atau 3174..." value="{{ $query }}" required>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-submit btn-lg w-100 fw-bold" style="border-radius:12px;font-size:14px;padding:12px;">
              <i class="bi bi-search me-1"></i> Periksa Status
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Result Card -->
    @if(!empty($query))
      @if($candidate)
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="border:1px solid #e2e8f0!important;">
          <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-navy">
              <i class="bi bi-person-badge me-2 text-danger"></i> Informasi Hasil Seleksi
            </h6>
            <x-badge-status :status="$candidate->status" />
          </div>
          <div class="card-body p-4">
            <div class="row g-3" style="font-size:13.5px">
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Nomor Registrasi</small>
                <strong class="font-monospace text-primary">{{ $candidate->recruitmentId }}</strong>
              </div>
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Tanggal Melamar</small>
                <strong>{{ $candidate->createdDate ?? '-' }}</strong>
              </div>
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Nama Lengkap</small>
                <strong>{{ $candidate->fullName }}</strong>
              </div>
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Posisi yang Dilamar</small>
                <strong class="text-navy">{{ $candidate->positionApplied }}</strong>
              </div>
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Kota Asal</small>
                <strong>{{ $candidate->city ?? '-' }}</strong>
              </div>
              <div class="col-sm-6">
                <small class="text-muted d-block" style="font-size:11.5px">Status Saat Ini</small>
                <div><x-badge-status :status="$candidate->status" /></div>
              </div>
            </div>

            <!-- Status Description Alert -->
            <div class="mt-4 p-3 rounded-3 border" style="background:#f8fafc;">
              <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill text-danger fs-4 me-3"></i>
                <div>
                  <h6 class="fw-bold mb-1" style="font-size:13px">Informasi Tahapan Seleksi</h6>
                  @php $st = strtolower($candidate->status ?? ''); @endphp
                  @if($st === 'new' || $st === 'baru')
                    <p class="small text-muted mb-0">Berkas Anda telah diterima dan sedang dalam antrean verifikasi administrasi tim HR.</p>
                  @elseif($st === 'screening')
                    <p class="small text-muted mb-0">Berkas Anda sedang dalam proses screening kualifikasi oleh recruiter kami.</p>
                  @elseif(str_contains($st, 'interview'))
                    <p class="small text-muted mb-0">Anda lolos ke tahap Wawancara. Harap pantau WhatsApp dan email Anda untuk jadwal undangan.</p>
                  @elseif($st === 'offering')
                    <p class="small text-muted mb-0">Selamat! Penawaran kerja (Job Offering) sedang dipersiapkan oleh tim HR kami.</p>
                  @elseif($st === 'accepted')
                    <p class="small text-success mb-0 fw-semibold">Selamat! Anda telah resmi diterima bergabung dengan PT MITO ELECTRONIC INDONESIA.</p>
                  @elseif($st === 'hold')
                    <p class="small text-muted mb-0">Profil Anda disimpan dalam talent pool kami untuk posisi yang relevan di masa mendatang.</p>
                  @elseif($st === 'rejected')
                    <p class="small text-muted mb-0">Terima kasih atas partisipasi Anda. Saat ini kualifikasi Anda belum sesuai dengan kebutuhan posisi yang dibuka.</p>
                  @else
                    <p class="small text-muted mb-0">Status seleksi Anda saat ini adalah <strong>{{ $candidate->status }}</strong>.</p>
                  @endif
                </div>
              </div>
            </div>

            <div class="mt-3 text-end">
              <a href="{{ route('public.career.self-update', ['id' => $candidate->recruitmentId]) }}" class="btn btn-sm btn-outline-danger" style="border-radius:8px">
                <i class="bi bi-pencil-square me-1"></i> Perbarui Data Kontak / Profil
              </a>
            </div>
          </div>
        </div>
      @else
        <div class="card border-0 shadow-sm p-4 text-center rounded-4" style="border:1px solid #fed7d7!important;background:#fff5f5;">
          <div class="text-danger mb-2"><i class="bi bi-x-circle fs-1"></i></div>
          <h5 class="fw-bold text-navy">Data Tidak Ditemukan</h5>
          <p class="text-muted mb-0" style="font-size:13px">Tidak ada lamaran yang terdaftar dengan kata kunci: <strong>{{ $query }}</strong>. Pastikan nomor ID atau NIK Anda sudah benar.</p>
        </div>
      @endif
    @endif

    <div class="text-center mt-4">
      <a href="{{ route('public.career.index') }}" class="text-decoration-none text-muted" style="font-size:13px">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Portal Karir
      </a>
    </div>

  </div>
</div>
@endsection
