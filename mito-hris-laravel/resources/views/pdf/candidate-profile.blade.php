<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Kandidat - {{ $candidate->fullName }}</title>
  <style>
    @page { margin: 22px 30px 28px 30px; }
    * { box-sizing: border-box; }
    body {
      font-family: Helvetica, Arial, sans-serif;
      font-size: 10px;
      color: #222;
      line-height: 1.45;
      margin: 0; padding: 0;
    }
    .header { width:100%; border-bottom:2px solid #111; padding-bottom:12px; margin-bottom:18px; }
    .header-table { width:100%; border-collapse:collapse; }
    .header-left  { width:70%; vertical-align:bottom; }
    .header-right { width:30%; vertical-align:bottom; text-align:right; }
    .candidate-name { font-size:25px; font-weight:bold; color:#111; margin:0; text-transform:uppercase; letter-spacing:0.3px; line-height:1.15; }
    .candidate-position { font-size:12px; color:#555; margin-top:5px; }
    .document-title { font-size:9px; color:#777; text-transform:uppercase; letter-spacing:1.2px; font-weight:bold; }
    .status { margin-top:5px; font-size:9px; font-weight:bold; text-transform:uppercase; }
    .status-pending   { color:#b07a00; }
    .status-new       { color:#555; }
    .status-accepted  { color:#222; }
    .status-hold      { color:#666; }
    .status-blacklist { color:#111; text-decoration:underline; }
    .status-default   { color:#555; }
    .meta { width:100%; margin:10px 0 18px; font-size:8.5px; color:#777; }
    .meta-table { width:100%; border-collapse:collapse; }
    .meta-item  { width:50%; padding:0 12px 0 0; vertical-align:top; }
    .meta-label { font-size:7.5px; color:#999; text-transform:uppercase; letter-spacing:0.7px; }
    .meta-value { font-size:9px; color:#333; margin-top:2px; }
    .section { margin-top:11px; }
    .section-header { width:100%; border-bottom:1px solid #222; padding-bottom:4px; margin-bottom:8px; }
    .section-title  { font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; color:#111; }
    .two-column  { width:100%; border-collapse:collapse; }
    .column-left  { width:50%; padding-right:18px; vertical-align:top; }
    .column-right { width:50%; padding-left:18px; vertical-align:top; border-left:1px solid #ddd; }
    .field { width:100%; margin-bottom:7px; }
    .field-label { display:block; font-size:7.5px; color:#888; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:1px; }
    .field-value { display:block; font-size:9.5px; color:#222; word-wrap:break-word; }
    .field-value strong { font-weight:bold; }
    .work-table { width:100%; border-collapse:collapse; }
    .work-row   { border-bottom:1px solid #eee; }
    .work-label { width:30%; padding:5px 10px 5px 0; vertical-align:top; font-size:8px; color:#777; text-transform:uppercase; letter-spacing:0.25px; }
    .work-value { width:70%; padding:5px 0; vertical-align:top; font-size:9.5px; color:#222; }
    .notes { border-left:3px solid #222; padding:7px 10px; background:#f7f7f7; font-size:9px; color:#333; line-height:1.5; }
    .notes-meta { margin-top:4px; font-size:7.5px; color:#999; }
    .footer { position:fixed; bottom:-12px; left:0; right:0; border-top:1px solid #ddd; padding-top:5px; font-size:7.5px; color:#999; }
    .footer-table { width:100%; border-collapse:collapse; }
    .footer-left  { width:70%; text-align:left; }
    .footer-right { width:30%; text-align:right; }
    .muted { color:#777; }
    .text-bold { font-weight:bold; }
    .nowrap { white-space:nowrap; }
  </style>
</head>
<body>

  @php
    $status = strtolower(trim($candidate->status ?? 'pending'));
    $statusClass = match($status) {
        'pending'   => 'status-pending',
        'new'       => 'status-new',
        'accepted'  => 'status-accepted',
        'hold'      => 'status-hold',
        'blacklist' => 'status-blacklist',
        default     => 'status-default',
    };
  @endphp

  <!-- HEADER -->
  <div class="header">
    <table class="header-table">
      <tr>
        <td class="header-left">
          <div class="candidate-name">{{ $candidate->fullName }}</div>
          <div class="candidate-position">{{ $candidate->positionApplied ?? '-' }}</div>
        </td>
        <td class="header-right">
          <div class="document-title">Candidate Profile</div>
          <div class="status {{ $statusClass }}">{{ strtoupper($candidate->status ?? 'Pending') }}</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- RECRUITMENT META -->
  <div class="meta">
    <table class="meta-table">
      <tr>
        <td class="meta-item">
          <div class="meta-label">Recruitment ID</div>
          <div class="meta-value">{{ $candidate->recruitmentId ?? '-' }}</div>
        </td>
        <td class="meta-item">
          <div class="meta-label">Tanggal Pendaftaran</div>
          <div class="meta-value">{{ $candidate->createdDate ?? '-' }}</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- INFORMASI PRIBADI -->
  <div class="section">
    <div class="section-header"><div class="section-title">Informasi Pribadi</div></div>
    <table class="two-column">
      <tr>
        <td class="column-left">
          <div class="field"><span class="field-label">Nama Lengkap</span><span class="field-value text-bold">{{ $candidate->fullName ?? '-' }}</span></div>
          <div class="field"><span class="field-label">NIK</span><span class="field-value">{{ $candidate->nik ?? '-' }}</span></div>
          <div class="field"><span class="field-label">Tanggal Lahir</span><span class="field-value">{{ $candidate->birthDate ?? '-' }}</span></div>
          <div class="field"><span class="field-label">Usia</span><span class="field-value">{{ $candidate->age ?? '-' }} Tahun</span></div>
          <div class="field"><span class="field-label">Jenis Kelamin</span><span class="field-value">{{ $candidate->gender ?? '-' }}</span></div>
        </td>
        <td class="column-right">
          <div class="field"><span class="field-label">Status Pernikahan</span><span class="field-value">{{ $candidate->maritalStatus ?? '-' }}</span></div>
          <div class="field"><span class="field-label">Email</span><span class="field-value">{{ $candidate->email ?? '-' }}</span></div>
          <div class="field"><span class="field-label">No. HP</span><span class="field-value">{{ $candidate->phone ?? '-' }}</span></div>
          <div class="field"><span class="field-label">Kota</span><span class="field-value">{{ $candidate->city ?? '-' }}</span></div>
          <div class="field"><span class="field-label">Alamat</span><span class="field-value">{{ $candidate->address ?? '-' }}</span></div>
        </td>
      </tr>
    </table>
  </div>

  <!-- INFORMASI PROFESIONAL -->
  <div class="section">
    <div class="section-header"><div class="section-title">Informasi Profesional</div></div>
    <table class="work-table">
      <tr class="work-row"><td class="work-label">Posisi Dilamar</td><td class="work-value text-bold">{{ $candidate->positionApplied ?? '-' }}</td></tr>
      <tr class="work-row"><td class="work-label">Pendidikan</td><td class="work-value">{{ $candidate->education ?? '-' }}</td></tr>
      <tr class="work-row"><td class="work-label">Pengalaman Kerja</td><td class="work-value">{{ $candidate->workExperience ?? '-' }}</td></tr>
      <tr class="work-row"><td class="work-label">Perusahaan Terakhir</td><td class="work-value">{{ $candidate->lastCompany ?? '-' }}</td></tr>
      <tr class="work-row"><td class="work-label">Status Bekerja Saat Ini</td><td class="work-value">{{ $candidate->currentEmploymentStatus ?? '-' }}</td></tr>
      <tr class="work-row"><td class="work-label">Kesediaan Bergabung</td><td class="work-value">{{ $candidate->availableToJoin ?? '-' }}</td></tr>
      <tr class="work-row">
        <td class="work-label">Ekspektasi Gaji</td>
        <td class="work-value">
          @php $salary = preg_replace('/[^0-9]/', '', $candidate->expectedSalary ?? '0'); @endphp
          Rp {{ number_format((float)$salary, 0, ',', '.') }}
        </td>
      </tr>
      <tr class="work-row"><td class="work-label">Sumber Rekrutmen</td><td class="work-value">{{ $candidate->recruitmentSource ?? '-' }}</td></tr>
    </table>
  </div>

  <!-- CATATAN HR -->
  @if(!empty($candidate->hrNotes))
    <div class="section">
      <div class="section-header"><div class="section-title">Catatan HR</div></div>
      <div class="notes">{{ $candidate->hrNotes }}</div>
      <div class="notes-meta">Terakhir diperbarui: {{ $candidate->updatedAt ?? '-' }}</div>
    </div>
  @endif

  <!-- FOOTER -->
  <div class="footer">
    <table class="footer-table">
      <tr>
        <td class="footer-left">MITO HRIS · Applicant Tracking System</td>
        <td class="footer-right">Generated {{ date('d F Y H:i') }}</td>
      </tr>
    </table>
  </div>

</body>
</html>
