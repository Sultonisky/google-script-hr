@php
  // Resolve display ID — candidate flow (CandidateData $candidate) or outsource (?id=EMP-OS-...)
  $displayId = $id ?? request('id', null);
  if (!$displayId) {
      $displayId = session('recruitment_id') ?? null;
  }

  // Detect outsource by ID prefix
  $isOutsource = $displayId && str_starts_with($displayId, 'EMP-OS');

  // Success message override
  $customMsg = session('message') ?? session('success') ?? null;
  if (!$customMsg) {
      $customMsg = $isOutsource
          ? "Data outsource Anda telah berhasil kami terima dan akan diproses oleh tim Human Resources MITO Group untuk keperluan administrasi HRIS.\n\nApabila ada pertanyaan, silakan menghubungi tim HR."
          : "Data Anda telah berhasil kami terima dan akan diproses oleh tim Human Resources MITO Group.\n\nApabila profil Anda sesuai dengan kebutuhan perusahaan, kami akan menghubungi Anda melalui email atau nomor telepon yang telah didaftarkan.";
  }
  $successTitle = $isOutsource ? 'Registrasi Berhasil' : 'Pendaftaran Berhasil';
  $recruitIdLabel = $isOutsource ? 'Employee ID Anda' : 'Recruitment ID Anda';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $successTitle }} - MITO Group</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --color-primary: #eb1c24;
      --color-primary-dark: #c41118;
      --color-navy: #0b2540;
      --color-accent: #fdb913;
      --color-bg: #f5f7fa;
      --color-surface: #ffffff;
      --color-text: #1f2937;
      --color-text-soft: #6b7280;
      --color-border: #e5e7eb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0;
      background: var(--color-bg);
      font-family: "Inter", sans-serif;
      color: var(--color-text);
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .main-content { flex: 1; }

    /* 1:1 from GAS css/form.html */
    .success-page {
      text-align: center;
      padding: 80px 20px;
      animation: fadeInUp 0.4s ease;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .success-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #ecfdf3 0%, #d1fae5 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      color: #166534;
      font-size: 48px;
      border: 4px solid #166534;
      box-shadow: 0 6px 20px -6px rgba(22, 101, 52, 0.2);
    }
    .success-title {
      font-size: 30px;
      font-weight: 800;
      color: var(--color-text);
      margin-bottom: 16px;
    }
    .success-message {
      font-size: 15px;
      color: var(--color-text-soft);
      line-height: 1.8;
      max-width: 540px;
      margin: 0 auto 32px;
      white-space: pre-line;
    }
    #successRecruitId {
      font-size: 14px;
      color: var(--color-primary);
      font-weight: 700;
      margin-bottom: 32px;
    }
    .success-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn-success-secondary {
      background: var(--color-surface);
      color: var(--color-text);
      border: 1.5px solid var(--color-border);
      padding: 12px 28px;
      font-size: 14px;
      font-weight: 600;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.25s ease;
      cursor: pointer;
    }
    .btn-success-secondary:hover {
      background: var(--color-bg);
      border-color: var(--color-primary);
      color: var(--color-primary);
    }
    .footer {
      background: #fff;
      border-top: 1px solid var(--color-border);
      text-align: center;
      padding: 24px 20px 20px;
      font-size: 12px;
      line-height: 1.8;
    }
    .footer .footer-brand-name { font-weight: 700; color: #6b7280; font-size: 13px; margin-bottom: 2px; }
    .footer .footer-tagline { color: #9ca3af; margin-bottom: 6px; }
    .footer .footer-help { max-width: 480px; margin: 0 auto; font-size: 11.5px; color: #9ca3af; }

    @media (max-width: 576px) {
      .success-page { padding: 60px 16px; }
      .success-title { font-size: 24px; }
      .success-actions { flex-direction: column; align-items: center; }
      .btn-success-secondary { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>
  <div class="main-content">
    <div class="success-page" id="successPage">
      <div class="success-icon">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h2 class="success-title">{{ $successTitle }}</h2>
      <p class="success-message">{{ $customMsg }}</p>
      @if($displayId)
        <p id="successRecruitId">{{ $recruitIdLabel }}: {{ $displayId }}</p>
      @else
        <p id="successRecruitId"></p>
      @endif
      <div class="success-actions">
        <a href="{{ route('public.career.index') }}" class="btn-success-secondary">
          <i class="bi bi-arrow-left"></i> Kembali ke Halaman Info
        </a>
      </div>
    </div>
  </div>

  <div class="footer">
    <div class="container">
      <div class="footer-brand-name">MITO HRIS</div>
      <div class="footer-tagline">&copy; {{ date('Y') }} &mdash; Crafted for Modern Human Resources</div>
      <div class="footer-help">Apabila mengalami kendala, silakan menghubungi Human Resources.</div>
    </div>
  </div>

  <script>
    // Set sessionStorage so if user navigates back to the form, session guard shows this page
    (function() {
      var displayId = '{{ addslashes($displayId ?? "1") }}';
      var isOutsource = {{ $isOutsource ? 'true' : 'false' }};
      try {
        if (isOutsource) {
          sessionStorage.setItem('msi_outsource_submitted', displayId);
        } else {
          sessionStorage.setItem('msi_form_submitted', displayId);
        }
      } catch(e) {}

      // Block browser back button to the form
      history.pushState(null, '', window.location.href);
      window.addEventListener('popstate', function() {
        history.pushState(null, '', window.location.href);
      });
    })();
  </script>
</body>
</html>
