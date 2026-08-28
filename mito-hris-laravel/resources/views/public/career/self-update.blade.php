@extends('layouts.public')

@section('title', 'Perbarui Data Lamaran - MITO Group')

@section('content')
    <!-- HERO (Red MITO Theme) -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-8">
                    <div class="hero-org">MITO Group</div>
                    <div class="hero-dept">Human Resources Department</div>
                    <h1 class="hero-title">Perbarui Data Lamaran Mandiri</h1>
                    <p class="hero-subtitle">Perbarui kontak aktif, alamat domisili, atau kualifikasi pendidikan untuk
                        pendaftaran nomor {{ $candidate->recruitmentId }}.</p>
                </div>
                <div class="col-lg-5 col-md-4 d-none d-md-flex justify-content-end align-items-center">
                    <img id="heroAvatarImg" src="{{ asset('assets/mito.png') }}" alt="MITO Official" class="hero-avatar-img"
                        loading="lazy">
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrap py-5">
        <div class="container" style="max-width:850px;">
            <div class="card border-0 shadow-sm rounded-4" style="border:1px solid #e2e8f0!important;">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h6 class="fw-bold mb-0 text-navy">
                        <i class="bi bi-pencil-square me-2 text-danger"></i> Formulir Perubahan Data Pelamar
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 mb-4 p-3 rounded-3"
                        style="background:#f0f7ff;color:#005BAC;font-size:13px">
                        <i class="bi bi-info-circle-fill me-1"></i> Anda sedang memperbarui data untuk pendaftaran:
                        <strong>{{ $candidate->recruitmentId }}</strong> ({{ $candidate->fullName }} &bull;
                        {{ $candidate->positionApplied }}).
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show p-3 mb-4 rounded-3 d-flex align-items-center gap-2"
                            role="alert">
                            <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close btn-sm p-3" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('public.career.self-update.store', ['id' => $candidate->recruitmentId]) }}"
                        method="POST">
                        @csrf

                        <div class="row g-3 mb-4" style="font-size:13px">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Alamat Email Aktif <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ old('email', $candidate->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nomor WhatsApp / HP <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="no_telp"
                                    value="{{ old('no_telp', $candidate->phone) }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Alamat Domisili Terbaru</label>
                                <textarea class="form-control" name="alamat" rows="2">{{ old('alamat', $candidate->address) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pendidikan Terakhir</label>
                                <input type="text" class="form-control" name="pendidikan"
                                    value="{{ old('pendidikan', $candidate->education) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pengalaman Kerja</label>
                                <input type="text" class="form-control" name="pengalaman_kerja"
                                    value="{{ old('pengalaman_kerja', $candidate->workExperience) }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Tautan / Link CV Terbaru</label>
                                <input type="url" class="form-control" name="cv_link"
                                    value="{{ old('cv_link', $candidate->cvLink) }}"
                                    placeholder="https://drive.google.com/...">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('public.career.check-status', ['q' => $candidate->recruitmentId]) }}"
                                class="btn btn-light border px-4" style="border-radius:10px;font-size:13.5px">Batal</a>
                            <button type="submit" class="btn btn-submit px-4" style="font-size:13.5px">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
