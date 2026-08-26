<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Performance Review – Evaluation Form – {{ $employee->fullName }}</title>
  <style>
    @page { margin: 24px 32px; }
    * { box-sizing: border-box; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 8.5pt;
      color: #000;
      line-height: 1.45;
    }

    /* ── Header logos ── */
    .logo-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .logo-text-mito { font-size: 18pt; font-weight: 900; color: #eb1c24; letter-spacing: -1px; }
    .logo-text-mito span { color: #000; }
    .logo-text-stein { font-size: 10pt; font-style: italic; font-weight: 700; color: #000; text-align: right; }

    /* ── Title ── */
    .form-title { font-size: 11pt; font-weight: bold; text-align: center; text-decoration: underline; margin: 6px 0 10px; }

    /* ── Employee info grid ── */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .info-table td { padding: 2px 4px; font-size: 8pt; vertical-align: top; }
    .info-label { width: 120px; font-weight: bold; }
    .info-colon { width: 10px; }

    /* ── Section guideline ── */
    .guideline { font-style: italic; font-size: 8pt; margin-bottom: 8px; line-height: 1.5; }
    .guideline strong { font-style: normal; }

    /* ── Main evaluation table ── */
    .eval-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .eval-table th, .eval-table td {
      border: 1px solid #888;
      padding: 4px 6px;
      vertical-align: top;
      font-size: 8pt;
    }
    .eval-table thead th { background: #eb1c24; color: #fff; font-weight: bold; text-align: center; }
    .eval-table .col-kompeten { width: 100px; font-weight: bold; font-style: italic; }
    .eval-table .col-action  { width: 130px; }
    .eval-table .col-indicator { }
    .eval-table .col-check   { width: 52px; text-align: center; }
    .eval-table .row-evidence td { background: #f0f0f0; font-style: italic; font-size: 7.5pt; }
    .eval-table .row-total td    { background: #e8f0fb; font-weight: bold; text-align: right; }
    .eval-table .row-total .total-val { font-size: 11pt; font-weight: 900; color: #eb1c24; }

    /* Checklist indicators */
    .chk-box {
      display: inline-block;
      width: 14px; height: 14px;
      border: 1.5px solid #555;
      border-radius: 2px;
      text-align: center;
      line-height: 13px;
      font-size: 10pt;
      font-weight: bold;
      vertical-align: middle;
    }
    .chk-yes { background: #eb1c24; color: #fff; border-color: #eb1c24; }
    .chk-no  { background: #fff;    color: #bbb; }

    /* ── Score summary box ── */
    .score-box {
      border: 2px solid #005BAC;
      border-radius: 4px;
      padding: 6px 10px;
      margin-bottom: 10px;
    }
    .score-box table { width: 100%; border-collapse: collapse; }
    .score-box td { padding: 2px 6px; font-size: 8pt; }
    .score-cat-row td { padding: 1px 4px; font-size: 7.5pt; }

    /* ── Decision section ── */
    .decision-box { border: 1px solid #888; padding: 6px 10px; margin-bottom: 10px; border-radius: 3px; }
    .decision-box .section-label { font-weight: bold; font-size: 8.5pt; margin-bottom: 4px; }
    .chk-inline { display: inline-block; width: 12px; height: 12px; border: 1.5px solid #555; border-radius: 2px; text-align: center; line-height: 11px; font-size: 9pt; font-weight: bold; vertical-align: middle; margin-right: 3px; }
    .chk-inline.checked { background: #eb1c24; color: #fff; border-color: #eb1c24; }

    /* ── Approval section ── */
    .approval-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .approval-table td { border: 1px solid #888; padding: 6px 8px; font-size: 8pt; vertical-align: top; width: 50%; }
    .approval-table .ap-header { font-weight: bold; font-size: 8pt; background: #f5f5f5; }
    .sign-space { height: 36px; display: block; }
  </style>
</head>
<body>

@php
  // ── Resolve company ──────────────────────────────────────────────
  $bulanId = ['Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

  $fmtDate = function(?string $d) use ($bulanId): string {
    if (!$d) return '-';
    $p = explode('-', preg_replace('/\s.*$/', '', $d));
    if (count($p) < 3) return $d;
    return (int)$p[2] . ' ' . ($bulanId[(int)$p[1] - 1] ?? '-') . ' ' . (int)$p[0];
  };

  $todayStr = date('j') . ' ' . $bulanId[(int)date('n') - 1] . ' ' . date('Y');

  // ── Employee fields (from template header) ───────────────────────
  $empId       = $employee->employeeId        ?? '-';
  $empName     = $employee->fullName          ?? '-';
  $empPT       = $employee->branchName        ?? '-';
  $empDept     = $employee->department        ?? '-';
  $empJoinDate = $fmtDate($employee->joinDate ?? null);
  $empPosition = $employee->jobPositionLocation ?? $employee->jobPosition ?? '-';
  $empLevel    = $employee->jobLevel          ?? '-';
  $empReviewer = $extraData['reviewer_name']  ?? ($employee->directSuperior ?? '-');

  // ── Indicator values (1 = checked, 0/'' = X) ─────────────────────
  $indVal = function(string $key) use ($evalData): bool {
    $v = $evalData[$key] ?? ($evalData['ind_' . $key] ?? 0);
    return !empty($v) && $v !== '0' && $v !== false;
  };

  // Per-competency totals (prefer stored; fall back to counting indicators)
  $intTotal = (int)($evalData['integrityTotal'] ?? (
    (int)$indVal('integrity_1') + (int)$indVal('integrity_2') +
    (int)$indVal('integrity_3') + (int)$indVal('integrity_4')
  ));
  $ciTotal  = (int)($evalData['ciTotal'] ?? (
    (int)$indVal('ci_1') + (int)$indVal('ci_2') +
    (int)$indVal('ci_3') + (int)$indVal('ci_4')
  ));
  $eeTotal  = (int)($evalData['eeTotal'] ?? (
    (int)$indVal('ee_1') + (int)$indVal('ee_2')
  ));
  $twTotal  = (int)($evalData['twTotal'] ?? (
    (int)$indVal('tw_1') + (int)$indVal('tw_2') + (int)$indVal('tw_3')
  ));
  $overall  = (int)($evalData['overallTotal'] ?? ($intTotal + $ciTotal + $eeTotal + $twTotal));

  $category = $evalData['category'] ?? match(true) {
    $overall >= 11 => 'Sangat Baik',
    $overall >= 8  => 'Baik',
    $overall >= 6  => 'Cukup',
    default        => 'Kurang',
  };

  // ── Decision ────────────────────────────────────────────────────
  $decision    = $evalData['decision']          ?? '-';
  $extDuration = $evalData['extensionDuration'] ?? ($evalData['extension_duration'] ?? '');
  $evalDate    = $evalData['evalDate']           ?? $todayStr;
  $evaluator   = $evalData['evaluator']          ?? ($extraData['evaluator'] ?? '-');

  $isPutus = str_contains($decision, 'Tidak Lulus') || str_contains($decision, 'Putus Kontrak') || str_contains($decision, 'Paklaring');
  $isPerp  = !$isPutus && (str_contains($decision, 'Perpanjang') || str_contains($decision, 'Evaluasi Ulang'));
  $isLulus = !$isPutus && !$isPerp && ($decision !== '-') && (str_contains($decision, 'Diangkat') || str_contains($decision, 'Tetap') || str_contains($decision, 'Lulus'));

  // ── Approval sign-off ────────────────────────────────────────────
  $apDept      = $extraData['approval_dept']      ?? '';
  $apDeptName  = $extraData['approval_dept_name'] ?? '';
  $apDeptDate  = $extraData['approval_dept_date'] ?? '';
  $apHrbp      = $extraData['approval_hrbp']      ?? '';
  $apHrbpName  = $extraData['approval_hrbp_name'] ?? '';
  $apHrbpDate  = $extraData['approval_hrbp_date'] ?? '';
@endphp

{{-- ── LOGO ROW ────────────────────────────────────────────────── --}}
<div class="logo-row">
  <div class="logo-text-mito">MiTO <span>electronics</span></div>
  <div class="logo-text-stein">Stein°<br><small style="font-weight:400;font-size:7pt">PREMIUM HEALTHY COOKWARE</small></div>
</div>
<hr style="border:none;border-top:2px solid #005BAC;margin:4px 0 8px">

{{-- ── FORM TITLE ─────────────────────────────────────────────── --}}
<div class="form-title">Performance Review – Evaluation Form</div>

{{-- ── EMPLOYEE INFORMATION ────────────────────────────────────── --}}
<table class="info-table">
  <tr>
    <td class="info-label">Employee ID</td>
    <td class="info-colon">:</td>
    <td>{{ $empId }}</td>
    <td class="info-label">Level</td>
    <td class="info-colon">:</td>
    <td>{{ $empLevel }}</td>
  </tr>
  <tr>
    <td class="info-label">Nama PT</td>
    <td class="info-colon">:</td>
    <td>{{ $empPT }}</td>
    <td class="info-label">Department</td>
    <td class="info-colon">:</td>
    <td>{{ $empDept }}</td>
  </tr>
  <tr>
    <td class="info-label">Nama Lengkap</td>
    <td class="info-colon">:</td>
    <td>{{ $empName }}</td>
    <td class="info-label">Tanggal Join</td>
    <td class="info-colon">:</td>
    <td>{{ $empJoinDate }}</td>
  </tr>
  <tr>
    <td class="info-label">Jabatan/Posisi</td>
    <td class="info-colon">:</td>
    <td colspan="3">{{ $empPosition }}</td>
  </tr>
  <tr>
    <td class="info-label">Nama Atasan Langsung (Reviewer)</td>
    <td class="info-colon">:</td>
    <td colspan="3">{{ $empReviewer }}</td>
  </tr>
</table>

{{-- ── PANDUAN PENILAIAN ───────────────────────────────────────── --}}
<div class="guideline">
  <em>Panduan Penilaian:</em><br>
  Berikan tanda checklist (✓) pada setiap Behavioral Indicator yang ditunjukkan.
  <strong>Total jumlah (✓)</strong> (pada kolom berwarna) isi kolom tersebut skor dengan jumlah checklist
  (✓) pada Behavioral Indicators (Contoh: jika terdapat 2 checklist, tuliskan jumlah 2).
</div>

{{-- ── HASIL PENILAIAN TABLE ──────────────────────────────────── --}}
<table class="eval-table">
  <thead>
    <tr>
      <th class="col-kompeten">Kompetensi</th>
      <th class="col-action">Key Actions</th>
      <th class="col-indicator">Behavioral Indicators</th>
      <th class="col-check">(✓) atau (X)</th>
    </tr>
  </thead>
  <tbody>

    {{-- ─── INTEGRITY ─────────────────────────────────────── --}}
    @php $rows_integrity = [
      ['integrity_1', 'Menyelesaikan minimal 90% tugas sesuai deadline.'],
      ['integrity_2', 'Memberikan update progres pekerjaan secara rutin.'],
      ['integrity_3', 'Menindaklanjuti permasalahan sesuai SLA.'],
      ['integrity_4', 'Tidak terdapat pelanggaran prosedur, kebijakan, atau komitmen kerja yang telah disepakati.'],
    ]; @endphp

    @foreach($rows_integrity as $i => $row)
    <tr>
      @if($i === 0)
      <td class="col-kompeten" rowspan="{{ count($rows_integrity) + 2 }}" style="font-style:italic;vertical-align:middle">Integrity</td>
      <td class="col-action" rowspan="{{ count($rows_integrity) }}" style="vertical-align:top">
        <strong>Take Accountability</strong><br>
        <span style="font-weight:normal">Bertanggung jawab atas tindakan, keputusan &amp; hasil kerja yang dilakukan.</span>
      </td>
      @endif
      <td>{{ $row[1] }}</td>
      <td class="col-check">
        <span class="chk-box {{ $indVal($row[0]) ? 'chk-yes' : 'chk-no' }}">{{ $indVal($row[0]) ? '✓' : 'X' }}</span>
      </td>
    </tr>
    @endforeach

    {{-- Evidence row --}}
    <tr class="row-evidence">
      <td colspan="2">
        <em>Bentuk Evidence:</em><br>
        • Task/project tracker<br>
        • Weekly/deadline report
      </td>
      <td class="col-check" style="text-align:center;vertical-align:middle">
        <span class="row-total" style="font-size:9pt;font-weight:bold;color:#005BAC">Total (✓)</span>
      </td>
    </tr>
    <tr class="row-total">
      <td colspan="2" style="text-align:right;font-size:8pt;color:#005BAC">Total Jumlah (✓) — Integrity</td>
      <td class="col-check" style="text-align:center">
        <span class="total-val">{{ $intTotal }}</span>
        <span style="font-size:7pt;color:#555"> / 4</span>
      </td>
    </tr>

    {{-- ─── CONTINUOUS IMPROVEMENT ────────────────────────── --}}
    @php $rows_ci = [
      ['ci_1', 'Mengusulkan minimal 1 improvement atau solusi selama masa probation.'],
      ['ci_2', 'Berpartisipasi dalam minimal 1 project atau kegiatan tim atau perusahaan.'],
      ['ci_3', 'Mempelajari atau mengimplementasi proses, sistem atau knowledge baru yang dapat mendukung pekerjaan.'],
      ['ci_4', 'Mengambil tindakan awal terhadap masalah yang ditemukan sebelum dilakukan eskalasi.'],
    ]; @endphp

    @foreach($rows_ci as $i => $row)
    <tr>
      @if($i === 0)
      <td class="col-kompeten" rowspan="{{ count($rows_ci) + 2 }}" style="font-style:italic;vertical-align:middle">Continuous<br>Improvement</td>
      <td class="col-action" rowspan="{{ count($rows_ci) }}" style="vertical-align:top">
        <strong>Proactive Contribution</strong><br>
        <span style="font-weight:normal">Aktif mencari kesempatan belajar &amp; meningkatkan cara kerja.</span>
      </td>
      @endif
      <td>{{ $row[1] }}</td>
      <td class="col-check">
        <span class="chk-box {{ $indVal($row[0]) ? 'chk-yes' : 'chk-no' }}">{{ $indVal($row[0]) ? '✓' : 'X' }}</span>
      </td>
    </tr>
    @endforeach

    <tr class="row-evidence">
      <td colspan="2">
        <em>Bentuk Evidence:</em><br>
        • Assignment project &nbsp;• Project report &nbsp;• Coaching form
      </td>
      <td class="col-check" style="text-align:center;vertical-align:middle">
        <span style="font-size:9pt;font-weight:bold;color:#005BAC">Total (✓)</span>
      </td>
    </tr>
    <tr class="row-total">
      <td colspan="2" style="text-align:right;font-size:8pt;color:#005BAC">Total Jumlah (✓) — Continuous Improvement</td>
      <td class="col-check" style="text-align:center">
        <span class="total-val">{{ $ciTotal }}</span>
        <span style="font-size:7pt;color:#555"> / 4</span>
      </td>
    </tr>

    {{-- ─── EXECUTION EXCELLENCE ──────────────────────────── --}}
    @php $rows_ee = [
      ['ee_1', 'Tingkat kesalahan atau rework tidak melebihi kesepakatan yang telah ditetapkan.'],
      ['ee_2', 'Hasil pekerjaan dapat digunakan atau diselesaikan tanpa koreksi mayor.'],
    ]; @endphp

    @foreach($rows_ee as $i => $row)
    <tr>
      @if($i === 0)
      <td class="col-kompeten" rowspan="{{ count($rows_ee) + 2 }}" style="font-style:italic;vertical-align:middle">Execution<br>Excellence</td>
      <td class="col-action" rowspan="{{ count($rows_ee) }}" style="vertical-align:top">
        <strong>Deliver Quality Results</strong><br>
        <span style="font-weight:normal">Menyelesaikan pekerjaan dengan kualitas yang baik.</span>
      </td>
      @endif
      <td>{{ $row[1] }}</td>
      <td class="col-check">
        <span class="chk-box {{ $indVal($row[0]) ? 'chk-yes' : 'chk-no' }}">{{ $indVal($row[0]) ? '✓' : 'X' }}</span>
      </td>
    </tr>
    @endforeach

    <tr class="row-evidence">
      <td colspan="2">
        <em>Bentuk Evidence:</em><br>
        Dokumentasi kegiatan / bentuk konkrit evidence lain yang dapat menampilkan bukti nyata achievement.
      </td>
      <td class="col-check" style="text-align:center;vertical-align:middle">
        <span style="font-size:9pt;font-weight:bold;color:#005BAC">Total (✓)</span>
      </td>
    </tr>
    <tr class="row-total">
      <td colspan="2" style="text-align:right;font-size:8pt;color:#005BAC">Total Jumlah (✓) — Execution Excellence</td>
      <td class="col-check" style="text-align:center">
        <span class="total-val">{{ $eeTotal }}</span>
        <span style="font-size:7pt;color:#555"> / 2</span>
      </td>
    </tr>

    {{-- ─── TEAMWORK ───────────────────────────────────────── --}}
    @php $rows_tw = [
      ['tw_1', 'Berpartisipasi aktif dalam meeting, diskusi, atau koordinasi yang berkaitan dengan pekerjaan.'],
      ['tw_2', 'Menindaklanjuti permintaan atau kebutuhan stakeholder internal sesuai SLA.'],
      ['tw_3', 'Tidak terdapat keluhan mayor terkait koordinasi atau kerja sama selama masa probation.'],
    ]; @endphp

    @foreach($rows_tw as $i => $row)
    <tr>
      @if($i === 0)
      <td class="col-kompeten" rowspan="{{ count($rows_tw) + 2 }}" style="font-style:italic;vertical-align:middle">Teamwork</td>
      <td class="col-action" rowspan="{{ count($rows_tw) }}" style="vertical-align:top">
        <strong>Supportive Collaboration</strong><br>
        <span style="font-weight:normal">Berkolaborasi dan memberikan dukungan untuk mencapai tujuan/target bersama.</span>
      </td>
      @endif
      <td>{{ $row[1] }}</td>
      <td class="col-check">
        <span class="chk-box {{ $indVal($row[0]) ? 'chk-yes' : 'chk-no' }}">{{ $indVal($row[0]) ? '✓' : 'X' }}</span>
      </td>
    </tr>
    @endforeach

    <tr class="row-evidence">
      <td colspan="2">
        <em>Bentuk Evidence:</em><br>
        • Stakeholder feedback &nbsp;• Observasi dari atasan langsung
      </td>
      <td class="col-check" style="text-align:center;vertical-align:middle">
        <span style="font-size:9pt;font-weight:bold;color:#005BAC">Total (✓)</span>
      </td>
    </tr>
    <tr class="row-total">
      <td colspan="2" style="text-align:right;font-size:8pt;color:#005BAC">Total Jumlah (✓) — Teamwork</td>
      <td class="col-check" style="text-align:center">
        <span class="total-val">{{ $twTotal }}</span>
        <span style="font-size:7pt;color:#555"> / 3</span>
      </td>
    </tr>

    {{-- ─── TOTAL KESELURUHAN ─────────────────────────────── --}}
    <tr style="background:#e8f0fb">
      <td colspan="3" style="text-align:right;font-weight:bold;font-size:9pt;color:#005BAC">
        Total Jumlah Keseluruhan (✓)
      </td>
      <td class="col-check" style="text-align:center">
        <span style="font-size:14pt;font-weight:900;color:#005BAC">{{ $overall }}</span>
        <span style="font-size:7pt;color:#555"> / 13</span>
      </td>
    </tr>

  </tbody>
</table>

{{-- ── KATEGORI TOTAL NILAI ─────────────────────────────────────── --}}
<div class="score-box">
  <table>
    <tr>
      <td style="font-weight:bold;font-size:8.5pt" colspan="8">
        Kategori Total Nilai [berdasarkan total jumlah (✓)]:
      </td>
    </tr>
    <tr class="score-cat-row">
      @php
        $cats = [
          ['11 – 13', 'Sangat Baik', '#005BAC'],
          ['8 – 10',  'Baik',        '#166534'],
          ['6 – 7',   'Cukup',       '#d97706'],
          ['3 – 5',   'Kurang',       '#991b1b'],
        ];
      @endphp
      @foreach($cats as $cat)
      <td style="width:12%;padding:2px 3px">
        <span style="display:inline-block;padding:1px 5px;border:1.5px solid {{ $cat[2] }};border-radius:3px;
              {{ $category === $cat[1] ? 'background:' . $cat[2] . ';color:#fff;' : 'color:' . $cat[2] . ';' }}
              font-weight:700;font-size:8pt">{{ $cat[0] }}</span>
      </td>
      <td style="width:13%;font-size:8pt;color:{{ $category === $cat[1] ? $cat[2] : '#555' }};
                 {{ $category === $cat[1] ? 'font-weight:bold' : '' }}">
        : {{ $cat[1] }}
      </td>
      @endforeach
    </tr>
  </table>
</div>

{{-- ── KEPUTUSAN ────────────────────────────────────────────────── --}}
<div class="decision-box">
  <div class="section-label">Berdasarkan penilaian di atas, karyawan tersebut dapat dilanjutkan:</div>
  <div style="margin-bottom:4px">
    <span class="chk-inline {{ $isLulus && !$isPerp && !$isPutus ? 'checked' : '' }}">{{ $isLulus && !$isPerp && !$isPutus ? '✓' : '' }}</span>
    <strong>Diangkat sebagai Karyawan Tetap</strong>
  </div>
  <div style="margin-bottom:4px">
    <span class="chk-inline {{ $isPutus ? 'checked' : '' }}">{{ $isPutus ? '✓' : '' }}</span>
    <strong>Tidak Lulus</strong>
  </div>
  <div style="margin-bottom:4px">
    <span class="chk-inline {{ $isPerp ? 'checked' : '' }}">{{ $isPerp ? '✓' : '' }}</span>
    <strong>Perpanjang Kontrak</strong>
    @if($isPerp && $extDuration)
      &nbsp;&nbsp;
      <span class="chk-inline {{ $extDuration === '3 Bulan'  ? 'checked' : '' }}">{{ $extDuration === '3 Bulan'  ? '✓' : '' }}</span> 3 Bulan &nbsp;
      <span class="chk-inline {{ $extDuration === '6 Bulan'  ? 'checked' : '' }}">{{ $extDuration === '6 Bulan'  ? '✓' : '' }}</span> 6 Bulan &nbsp;
      <span class="chk-inline {{ $extDuration === '12 Bulan' ? 'checked' : '' }}">{{ $extDuration === '12 Bulan' ? '✓' : '' }}</span> 12 Bulan
    @else
      &nbsp;&nbsp;
      <span class="chk-inline"></span> 3 Bulan &nbsp;
      <span class="chk-inline"></span> 6 Bulan &nbsp;
      <span class="chk-inline"></span> 12 Bulan
    @endif
  </div>
</div>

{{-- ── APPROVAL / SIGN-OFF ─────────────────────────────────────── --}}
<table class="approval-table">
  <tr>
    <td class="ap-header">Tanggal: {{ $apDeptDate ? $fmtDate($apDeptDate) : $todayStr }}</td>
    <td class="ap-header">Tanggal: {{ $apHrbpDate ? $fmtDate($apHrbpDate) : $todayStr }}</td>
  </tr>
  <tr>
    <td>
      <span class="chk-inline {{ $apDept === 'Setuju' ? 'checked' : '' }}">{{ $apDept === 'Setuju' ? '✓' : '' }}</span> Setuju &nbsp;&nbsp;
      <span class="chk-inline {{ $apDept === 'Tidak'  ? 'checked' : '' }}">{{ $apDept === 'Tidak'  ? '✓' : '' }}</span> Tidak
    </td>
    <td>
      <span class="chk-inline {{ $apHrbp === 'Setuju' ? 'checked' : '' }}">{{ $apHrbp === 'Setuju' ? '✓' : '' }}</span> Setuju &nbsp;&nbsp;
      <span class="chk-inline {{ $apHrbp === 'Tidak'  ? 'checked' : '' }}">{{ $apHrbp === 'Tidak'  ? '✓' : '' }}</span> Tidak
    </td>
  </tr>
  <tr>
    <td>
      Nama: <span class="sign-space"></span>
      <strong>{{ $apDeptName ?: '____________________' }}</strong><br>
      <em>Department Manager/Head</em>
    </td>
    <td>
      Nama: <span class="sign-space"></span>
      <strong>{{ $apHrbpName ?: '____________________' }}</strong><br>
      <em>HRBP / HR &amp; Legal Manager</em>
    </td>
  </tr>
</table>

<div style="font-size:7pt;color:#aaa;text-align:right;margin-top:6px">
  Evaluation Form, pg. 1 &nbsp;|&nbsp; Generated: {{ $todayStr }} &nbsp;|&nbsp; Eval ID: {{ $evalData['evalId'] ?? '-' }}
</div>

</body>
</html>
