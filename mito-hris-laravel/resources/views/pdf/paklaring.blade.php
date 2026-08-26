<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Kerja (Paklaring) - {{ $employee->fullName }}</title>
  <style>
    @page { margin: 30px 40px; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 11px;
      color: #1f2937;
      line-height: 1.55;
      text-align: justify;
    }
    .doc-title  { font-size: 12.5pt; font-weight: bold; text-align: center; text-transform: uppercase; color: #000000; margin-top: 6px; margin-bottom: 12px; }
    .kv-table   { width: 100%; border-collapse: collapse; margin: 4px 0; }
    .kv-table td { padding: 2px 3px; vertical-align: top; font-size: 8.5pt; }
    .kv-label   { width: 120px; font-weight: bold; }
    .kv-colon   { width: 12px; }
    .kv-value   { }
    .sign-table { width: 100%; margin-top: 35px; page-break-inside: avoid; }
    .sign-table td { text-align: right; padding-right: 0; vertical-align: top; font-size: 8.5pt; }
    .sign-space { height: 50px; display: block; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // ── 1:1 GAS exportPaklaringPDF() ────────────────────────────

    // Employee name: 1:1 GAS emp.fullName
    $empName = $employee->fullName ?? '-';

    // joinDate: 1:1 GAS emp.joinDate → fmtDateId()
    // GAS fmtDateId splits by '-', format: "d Bulan YYYY"
    $bulanId = ['Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];

    $fmtDateId = function(?string $isoStr) use ($bulanId): string {
        if (!$isoStr || $isoStr === '-') return '-';
        $p = explode('-', preg_replace('/\s.*$/', '', $isoStr)); // strip time
        if (count($p) < 3) return $isoStr;
        $d = (int)$p[2]; $m = (int)$p[1]; $y = (int)$p[0];
        return $d . ' ' . ($bulanId[$m - 1] ?? '-') . ' ' . $y;
    };

    $joinFmt = $fmtDateId($employee->joinDate);

    // position: 1:1 GAS emp.position || emp.positionCurrent || 'Karyawan'
    // GAS uses emp.position (from getProbationList which maps Job Position (Location) → position)
    $positionStr = $employee->jobPositionLocation ?? $employee->jobPosition ?? 'Karyawan';

    // endDate: 1:1 GAS emp.lastWorkingDate || emp.resignDate || todayStr
    // GAS uses lastWorkingDate first, then resignDate — NOT endDateContract
    // In Employee sheet: Resign Date = lastWorkingDate for Terminated employees
    $endDate = $extraData['last_working_date'] ?? $extraData['effective_date']
               ?? $employee->resignDate ?? null;
    $endFmt = $endDate ? $fmtDateId($endDate) : (date('j') . ' ' . $bulanId[(int)date('n')-1] . ' ' . date('Y'));

    // companyName: 1:1 GAS comp.name
    $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';

    // todayStr: 1:1 GAS — format "d Bulan YYYY"
    $todayStr = date('j') . ' ' . $bulanId[(int)date('n') - 1] . ' ' . date('Y');

    // city: 1:1 GAS — HARDCODED 'Jakarta' in exportPaklaringPDF
    // GAS: doc.text('Jakarta, ' + todayStr, sigX, yPos)
    $sigCity = 'Jakarta';
  @endphp

  {{-- ── JUDUL (1:1 GAS: 'SURAT KETERANGAN KERJA', no nomor line) --}}
  <div class="doc-title">SURAT KETERANGAN KERJA</div>

  {{-- ── PEMBUKA (1:1 GAS: 'Yang bertandatangan di bawah ini:') --}}
  <p style="font-size:9pt;">Yang bertandatangan di bawah ini:</p>

  {{-- ── SIGNER BLOCK (1:1 GAS: Nama = Hisar Hesti, Jabatan = HR & Legal Manager) --}}
  <table class="kv-table">
    <tr>
      <td class="kv-label">Nama</td>
      <td class="kv-colon">:</td>
      <td class="kv-value"><strong>Hisar Hesti</strong></td>
    </tr>
    <tr>
      <td class="kv-label">Jabatan</td>
      <td class="kv-colon">:</td>
      <td class="kv-value">Human Resources (HR) &amp; Legal Manager</td>
    </tr>
  </table>

  {{-- ── 'Dengan ini menerangkan bahwa:' ────────────────────── --}}
  <p style="font-size:9pt;margin-top:8px;">Dengan ini menerangkan bahwa:</p>

  {{-- ── EMPLOYEE NAME only (1:1 GAS: only Nama row) ─────────── --}}
  <table class="kv-table">
    <tr>
      <td class="kv-label">Nama</td>
      <td class="kv-colon">:</td>
      <td class="kv-value"><strong>{{ $empName }}</strong></td>
    </tr>
  </table>

  {{-- ── PARAGRAF UTAMA (1:1 GAS para1 exact text) ─────────── --}}
  <p style="font-size:9pt;margin-top:12px;">
    Telah bekerja di <strong>{{ $companyName }}</strong> terhitung sejak
    <strong>{{ $joinFmt }}</strong> dengan jabatan terakhir sebagai
    <strong>{{ $positionStr }}</strong>.
    Masa kerja di <strong>{{ $companyName }}</strong> telah berakhir pada tanggal
    <strong>{{ $endFmt }}</strong>.
  </p>

  {{-- ── PARAGRAF PENUTUP (1:1 GAS para2 exact text) ───────── --}}
  <p style="font-size:9pt;">
    Demikian surat keterangan ini dibuat dan digunakan sebagaimana mestinya.
  </p>

  {{-- ── TANDA TANGAN (1:1 GAS: kanan, 'Jakarta, '+todayStr, Hormat Kami, Hisar Hesti, 'HR & Legal Manager') --}}
  <table class="sign-table">
    <tr>
      <td>
        {{ $sigCity }}, {{ $todayStr }}<br>
        Hormat Kami,<br>
        <span class="sign-space"></span>
        @include('pdf.components.hr-sign')
        <strong><u>Hisar Hesti</u></strong><br>
        HR &amp; Legal Manager
      </td>
    </tr>
  </table>

</body>
</html>
