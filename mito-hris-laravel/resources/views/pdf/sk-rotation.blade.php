<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keputusan Rotasi / Mutasi - {{ $employee->fullName }}</title>
  <style>
    @page { margin: 30px 40px; }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 11px;
      color: #1f2937;
      line-height: 1.55;
      text-align: justify;
    }
    .doc-title { font-size: 14pt; font-weight: bold; text-align: center; text-transform: uppercase; color: #0b2540; margin-top: 10px; }
    .doc-no    { font-size: 10pt; text-align: center; color: #6b7280; margin-bottom: 20px; }
    .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .data-table td { padding: 4px 6px; vertical-align: top; }
    .label  { width: 30%; font-weight: bold; color: #1f2937; }
    .colon  { width: 3%; }
    .value  { width: 67%; }
    .sign-table { width: 100%; margin-top: 30px; }
    .sign-table td { width: 50%; vertical-align: top; }
    .sign-space { height: 55px; }
    .closing-text { margin-top: 12px; margin-bottom: 8px; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // 1:1 dengan GAS exportRotationLetterPDF
    $typeMap = ['Promosi' => 'PROMOSI', 'Demosi' => 'DEMOSI', 'Mutasi' => 'MUTASI', 'Rotasi' => 'ROTASI'];
    $rawType     = $extraData['rotation_type'] ?? $extraData['rotationType'] ?? ($employee->typeOfRotation ?? 'Rotasi');
    $typeLabel   = $typeMap[$rawType] ?? strtoupper($rawType);

    // Nomor SK — dari request, dari employee sheet, atau auto-generate
    $romanMonth  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $skNumber    = $extraData['sk_number'] ?? $extraData['skNumber']
                    ?? ($employee->nomorSk ?? '')
                    ?: ('001/HRD-SK/' . ($company['brand'] ?? 'MITO') . '/' . $romanMonth[date('n')-1] . '/' . date('Y'));

    // Posisi/departemen/cabang SEBELUMNYA (FORMER):
    //   1. priority: dikirim via query param extraData
    //   2. fallback: kolom "Job Position (Former)" di Employee sheet (jobPositionFormer)
    //   3. terakhir: gunakan jobPosition saat ini (hanya safety jika data belum ter-sync)
    $formerPos   = trim($extraData['old_job_position'] ?? $extraData['oldPosition'] ?? '')
                    ?: ($employee->jobPositionFormer ?? '')
                    ?: ($employee->jobPosition        ?? '-');
    $formerDept  = trim($extraData['old_department'] ?? $extraData['oldDept'] ?? '')
                    ?: ($employee->department         ?? '-');
    $formerBranch= trim($extraData['old_branch_name'] ?? $extraData['oldBranch'] ?? '')
                    ?: ($employee->branchName         ?? ($company['name'] ?? '-'));

    // Posisi/departemen/cabang BARU:
    //   1. priority: dikirim via query param extraData (paling akurat saat call saat submit)
    //   2. fallback: employee sheet terbaru (jika memang sudah di-save terbaru)
    $newPos      = trim($extraData['new_job_position'] ?? $extraData['newJobPosition'] ?? '')
                    ?: ($employee->jobPosition        ?? $formerPos);
    $newDept     = trim($extraData['new_department']   ?? $extraData['newDepartment']  ?? '')
                    ?: ($employee->department         ?? $formerDept);
    $newBranch   = trim($extraData['new_branch_name']  ?? $extraData['newBranchName']  ?? '')
                    ?: ($employee->branchName         ?? $formerBranch);

    $effectiveDate = $extraData['effective_date'] ?? $extraData['effectiveDate']
                    ?? ($employee->rotationDate ?? date('Y-m-d'));
    if ($effectiveDate && preg_match('/^\d{4}-\d{2}-\d{2}/', $effectiveDate)) {
        $effectiveDate = \Carbon\Carbon::parse($effectiveDate)->translatedFormat('d F Y');
    }

    $reason      = $extraData['reason'] ?? $extraData['notes'] ?? ($employee->hrNotes ? last(explode("\n", $employee->hrNotes)) : '-');
    $companyName = $company['name']     ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyCity = $company['city']     ?? 'Jakarta';
    $todayFmt    = date('d F Y');
  @endphp

  <div class="doc-title">SURAT KEPUTUSAN {{ $typeLabel }}</div>
  <div class="doc-no">Nomor: {{ $skNumber }}</div>

  <p>Yang bertanda tangan di bawah ini, Manajemen / HRD <strong>{{ $companyName }}</strong>, dengan ini menetapkan:</p>

  <table class="data-table">
    <tr><td class="label">Nama Karyawan</td><td class="colon">:</td><td class="value"><strong>{{ $employee->fullName }}</strong></td></tr>
    <tr><td class="label">Employee ID</td><td class="colon">:</td><td class="value">{{ $employee->employeeId }}</td></tr>
    <tr><td class="label">NIK / No. KTP</td><td class="colon">:</td><td class="value">{{ ltrim($employee->nikNpwp ?? '-', "'") }}</td></tr>
    <tr><td class="label">Entitas Perusahaan</td><td class="colon">:</td><td class="value">{{ $companyName }}</td></tr>
    <tr><td class="label">Jabatan Sebelumnya</td><td class="colon">:</td><td class="value">{{ $formerPos }}</td></tr>
    <tr><td class="label">Jabatan Baru</td><td class="colon">:</td><td class="value"><strong>{{ $newPos }}</strong></td></tr>
    <tr><td class="label">Departemen Sebelumnya</td><td class="colon">:</td><td class="value">{{ $formerDept }}</td></tr>
    <tr><td class="label">Departemen Baru</td><td class="colon">:</td><td class="value"><strong>{{ $newDept }}</strong></td></tr>
    <tr><td class="label">Jenis Penetapan</td><td class="colon">:</td><td class="value">{{ $rawType }}</td></tr>
    <tr><td class="label">Tanggal Efektif</td><td class="colon">:</td><td class="value"><strong>{{ $effectiveDate }}</strong></td></tr>
    <tr><td class="label">Alasan / Pertimbangan</td><td class="colon">:</td><td class="value">{{ $reason }}</td></tr>
  </table>

  <p class="closing-text">Demikian Surat Keputusan ini diterbitkan untuk dilaksanakan dengan penuh rasa tanggung jawab. Segala hak, kewajiban, dan wewenang yang melekat pada jabatan baru berlaku efektif sejak tanggal yang telah ditetapkan.</p>

  {{-- Tanda Tangan — HR kiri, Karyawan kanan (1:1 GAS) --}}
  <table class="sign-table">
    <tr>
      <td style="text-align:left">
        {{ $companyCity }}, {{ $todayFmt }}<br>
        Hormat Kami,<br>
        <strong>{{ $companyName }}</strong><br>
        @include('pdf.components.hr-sign')
        <strong><u>Hisar Hesti</u></strong><br>
        Human Resources (HR) &amp; Legal Manager
      </td>
      <td style="text-align:left; padding-left:20px">
        <br><br>
        Karyawan Yang Bersangkutan,
        <div class="sign-space"></div>
        <strong><u>{{ $employee->fullName }}</u></strong><br>
        {{ $employee->employeeId }}
      </td>
    </tr>
  </table>

</body>
</html>
