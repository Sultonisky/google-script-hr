<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Offering Letter - {{ $candidate->fullName ?? '-' }}</title>
  <style>
    @page { margin: 28px 36px; size: A4; }

    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 9.5pt;
      color: #111111;
      line-height: 1.55;
      text-align: justify;
    }

    /* ── Tanggal pojok kanan ── */
    .doc-date {
      text-align: right;
      font-size: 9pt;
      margin-bottom: 14px;
      margin-top: 4px;
    }

    /* ── Kepada / Perihal ── */
    .recipient-block {
      margin-bottom: 10px;
    }
    .recipient-block p {
      margin: 0 0 1px 0;
    }

    /* ── Tabel detail jabatan ── */
    .detail-table {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0 6px 0;
    }
    .detail-table td {
      padding: 3px 4px;
      font-size: 9pt;
      vertical-align: top;
    }
    .detail-label {
      width: 155px;
      font-weight: bold;
    }
    .detail-colon { width: 10px; }
    .detail-value { width: auto; }

    /* ── Tabel remunerasi ── */
    .remu-table {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0 6px 0;
    }
    .remu-table th {
      background: #f5f5f5;
      font-weight: bold;
      font-size: 9pt;
      padding: 5px 8px;
      text-align: left;
      border: 1px solid #cccccc;
    }
    .remu-table td {
      padding: 4px 8px;
      font-size: 9pt;
      border: 1px solid #dddddd;
      vertical-align: middle;
    }
    .remu-table tr.total td {
      font-weight: bold;
      border-top: 1.5px solid #555555;
    }
    .remu-table td.align-right {
      text-align: right;
      white-space: nowrap;
    }

    /* ── Fasilitas list ── */
    .facility-list {
      margin: 4px 0 4px 16px;
      padding: 0;
    }
    .facility-list li {
      margin-bottom: 2px;
      font-size: 9pt;
    }

    /* ── Tanda tangan ── */
    .sign-block {
      margin-top: 18px;
      text-align: left;
      page-break-inside: avoid;
    }
    .sign-image-area {
      height: 54px;
      margin: 4px 0 0 0;
    }
    .sign-image-area img {
      height: 50px;
      width: auto;
      max-width: 150px;
    }

    /* ── Halaman 2: Konfirmasi kandidat ── */
    .page-break { page-break-before: always; }

    .confirm-block {
      padding-top: 12px;
    }
    .confirm-sign-table {
      width: 55%;
      margin-top: 30px;
      border-collapse: collapse;
    }
    .confirm-sign-table td {
      padding: 0;
      vertical-align: top;
    }
    .sign-line {
      border-bottom: 1px solid #333;
      width: 200px;
      margin-bottom: 5px;
    }

    p { margin: 5px 0; }

    strong { font-weight: bold; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // ── Resolve semua data (1:1 GAS exportOfferingLetterPDF) ──────────
    $fullName         = $candidate->fullName        ?? '-';
    $candidateCity    = $candidate->city            ?? '-';

    $companyName      = $company['name']            ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyCity      = $company['city']            ?? 'Jakarta';

    // Tanggal dokumen — format "d F Y" Indonesia
    $bulanId = ['Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];
    $todayStr = date('j') . ' ' . $bulanId[(int)date('n') - 1] . ' ' . date('Y');

    // Batas berlaku: +3 hari dari sekarang (1:1 GAS)
    $expiryStr = date('j', strtotime('+3 days')) . ' ' . $bulanId[(int)date('n', strtotime('+3 days')) - 1] . ' ' . date('Y', strtotime('+3 days'));

    // Data penawaran — dari extraData (form) atau kandidat offering fields
    $offerPosition       = $extraData['position']          ?? $candidate->offeringPosition        ?? $candidate->positionApplied ?? '-';
    $offerDivision       = $extraData['division']          ?? $candidate->offeringDivision         ?? '-';
    $offerDepartment     = $extraData['department']        ?? $candidate->offeringDepartment       ?? '';
    $offerJobLevel       = $extraData['job_level']         ?? $candidate->offeringJobLevel         ?? '';
    $offerLokasiKerja    = $extraData['lokasi_kerja']      ?? $candidate->offeringLokasiKerja      ?? $candidateCity;
    $offerEmployStatus   = $extraData['status']            ?? $extraData['employment_status']      ?? $candidate->offeringEmploymentStatus ?? 'Perjanjian Kerja Waktu Tertentu';
    $offerDuration       = $extraData['duration']          ?? $extraData['contract_duration']      ?? $candidate->offeringContractDuration ?? '12 bulan';
    $offerWorkingHours   = $extraData['working_hours']     ?? $candidate->offeringWorkingHours     ?? 'Senin – Jumat mulai pukul 08.00 – 17.00 WIB';

    // Gabungkan posisi + jabatan level jika ada
    $posisiJabatan = trim($offerPosition . ($offerJobLevel ? ' ' . $offerJobLevel : ''));

    // Gaji & tunjangan
    $cleanNum = fn($v) => (int) preg_replace('/[^0-9]/', '', (string)($v ?? '0'));
    $salaryBasic    = $cleanNum($extraData['salary_basic']    ?? $candidate->offeringSalaryBasic    ?? 0);
    $allowPulsa     = $cleanNum($extraData['allow_pulsa']     ?? $candidate->offeringAllowPulsa     ?? 0);
    $allowTransport = $cleanNum($extraData['allow_transport'] ?? $candidate->offeringAllowTransport ?? 0);
    $totalBruto     = $salaryBasic + $allowPulsa + $allowTransport;

    $fmtRp = fn($n) => 'Rp ' . number_format((int)$n, 0, ',', '.') . ',-';

    // Duration text untuk paragraf kontrak
    preg_match('/^(\d+)/i', $offerDuration, $dm);
    $durationNum = $dm[1] ?? '12';
    $durationUnit = stripos($offerDuration, 'tahun') !== false ? 'tahun' : 'bulan';
  @endphp

  {{-- ── Tanggal di pojok kanan (1:1 PDF sample) ──────────────── --}}
  <div class="doc-date">Tanggal: <strong>{{ $todayStr }}</strong></div>

  {{-- ── Kepada (1:1 PDF sample) ────────────────────────────────── --}}
  <div class="recipient-block">
    <p>Kepada Yth. <strong>{{ $fullName }}</strong></p>
    <p>Perihal: <strong>Penawaran Kerja (Offering Letter)</strong></p>
  </div>

  {{-- ── Salam pembuka ─────────────────────────────────────────── --}}
  <p>Dengan hormat,</p>
  <p>
    Berdasarkan hasil proses seleksi yang telah Saudara ikuti, kami dengan senang hati menyampaikan penawaran
    kerja untuk bergabung bersama <strong>{{ $companyName }}</strong> dengan ketentuan sebagai berikut:
  </p>

  {{-- ── Tabel detail jabatan (1:1 PDF sample) ─────────────────── --}}
  <table class="detail-table">
    <tr>
      <td class="detail-label">Posisi Jabatan</td>
      <td class="detail-colon">:</td>
      <td class="detail-value"><strong>{{ $posisiJabatan }}</strong></td>
    </tr>
    @if($offerDivision && $offerDivision !== '-')
    <tr>
      <td class="detail-label">Divisi</td>
      <td class="detail-colon">:</td>
      <td class="detail-value">{{ $offerDivision }}</td>
    </tr>
    @endif
    @if($offerDepartment && $offerDepartment !== '-')
    <tr>
      <td class="detail-label">Departemen</td>
      <td class="detail-colon">:</td>
      <td class="detail-value">{{ $offerDepartment }}</td>
    </tr>
    @endif
    <tr>
      <td class="detail-label">Lokasi Penempatan</td>
      <td class="detail-colon">:</td>
      <td class="detail-value">{{ $offerLokasiKerja }}</td>
    </tr>
    <tr>
      <td class="detail-label">Status Hubungan Kerja</td>
      <td class="detail-colon">:</td>
      <td class="detail-value">{{ $offerEmployStatus }}</td>
    </tr>
  </table>

  <p>
    Dengan masa kontrak selama <strong>{{ $durationNum }} {{ $durationUnit }}</strong> sesuai ketentuan perusahaan
    dan peraturan perundang-undangan yang berlaku.
  </p>

  {{-- ── Remunerasi ──────────────────────────────────────────── --}}
  <p>Perusahaan menawarkan paket remunerasi sebagai berikut:</p>

  <table class="remu-table">
    <thead>
      <tr>
        <th>Komponen</th>
        <th style="text-align:right; white-space:nowrap;">Nilai</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Gaji Pokok</td>
        <td class="align-right">{{ $fmtRp($salaryBasic) }}</td>
      </tr>
      @if($allowPulsa > 0)
      <tr>
        <td>Tunjangan Variabel (Pulsa)</td>
        <td class="align-right">{{ $fmtRp($allowPulsa) }}</td>
      </tr>
      @endif
      @if($allowTransport > 0)
      <tr>
        <td>Tunjangan Variabel (Transport)</td>
        <td class="align-right">{{ $fmtRp($allowTransport) }}</td>
      </tr>
      @endif
      <tr class="total">
        <td><strong>Total Penghasilan Bruto / Bulan</strong></td>
        <td class="align-right"><strong>{{ $fmtRp($totalBruto) }}</strong></td>
      </tr>
    </tbody>
  </table>

  <p>
    Kompensasi tersebut akan dikenakan potongan BPJS sesuai ketentuan perundangan yang berlaku.
    Perusahaan akan membayar PPH atas gaji saudara dan membayarkannya kepada Dirjen Pajak.
    Gaji akan dihitung mulai tanggal 21 hingga tanggal 20 bulan berikutnya dan akan langsung dibayarkan ke
    rekening bank Saudara yang telah didaftarkan ke Perusahaan pada tanggal 30 atau 31 di akhir bulan.
  </p>

  <p>Jam kerja Saudara di {{ $offerWorkingHours }}.</p>

  {{-- ── Fasilitas (1:1 PDF sample) ─────────────────────────── --}}
  <p><strong>Selain kompensasi di atas, Saudara akan memperoleh fasilitas sebagai berikut:</strong></p>
  <ul class="facility-list">
    <li>BPJS Kesehatan</li>
    <li>BPJS Ketenagakerjaan</li>
    <li>Tunjangan Hari Raya (THR)</li>
    <li>Cuti Tahunan sesuai kebijakan perusahaan</li>
    <li>Biaya Operasional yakni biaya bensin, tol, parkir saat melakukan perjalanan bisnis dapat di reimburse
        sesuai ketentuan Perusahaan.</li>
  </ul>

  <p>
    Saudara berhak mengikuti program insentif dan/atau bonus perusahaan sesuai pencapaian KPI, kinerja
    perusahaan, serta kebijakan yang berlaku.
  </p>

  <p>
    Saudara wajib menjaga kerahasiaan seluruh informasi, data, strategi bisnis, maupun dokumen perusahaan yang
    diperoleh selama masa kerja dan setelah hubungan kerja berakhir.
  </p>

  <p>
    Penawaran kerja ini berlaku sampai dengan tanggal <strong>{{ $expiryStr }}</strong>.
  </p>

  <p>
    Apabila Saudara menyetujui penawaran ini, mohon menandatangani dokumen ini dan mengembalikannya kepada kami
    sebelum batas waktu tersebut.
  </p>

  <p>
    Kami berharap Saudara dapat bergabung dan berkontribusi bersama <strong>{{ $companyName }}</strong> dalam
    mencapai tujuan dan pertumbuhan perusahaan.
  </p>

  {{-- ── Tanda Tangan (1:1 PDF sample: kiri bawah) ──────────── --}}
  <div class="sign-block">
    <p style="margin-bottom:1px;">{{ $companyCity }}, {{ $todayStr }}</p>
    <p style="margin-bottom:0;">Hormat kami,</p>
    <p style="margin-bottom:0;"><strong>{{ $companyName }}</strong></p>
    <div class="sign-image-area">
      @include('pdf.components.hr-sign')
    </div>
    <p style="margin:0; font-weight:bold; text-decoration:underline;">Hisar Hesti Pangaribuan</p>
    <p style="margin:0;">Human Resource &amp; Legal Manager</p>
  </div>

  {{-- ══════════════════════════════════════════════════════════
       HALAMAN 2 — Konfirmasi Kandidat (1:1 PDF sample halaman 2)
  ══════════════════════════════════════════════════════════ --}}
  <div class="page-break">
    <div class="confirm-block">
      <p>
        Dengan ini saya memberikan konfirmasi bahwa saya telah membaca, memahami, dan menyetujui semua
        persyaratan surat penawaran ini dan saya menerima penawaran ini sebagaimana disajikan.
      </p>
      <p>
        Saya bersedia bergabung di Perusahaan <strong>{{ $companyName }}</strong> pada tanggal:
        ____________.
      </p>

      <table class="confirm-sign-table">
        <tr>
          <td>
            <div class="sign-line"></div>
            <p style="margin:0; font-weight:bold;">{{ $fullName }}</p>
            <p style="margin:0;">Kandidat</p>
          </td>
        </tr>
      </table>
    </div>
  </div>

</body>
</html>
