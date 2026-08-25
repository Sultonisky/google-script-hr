<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SK Pengangkatan Karyawan Tetap - {{ $employee->fullName }}</title>

  <style>
    @page {
      margin: 30px 40px;
    }

    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 11px;
      color: #000000;
      line-height: 1.55;
      text-align: justify;
    }

    /* =========================================================
       DOCUMENT HEADER
       ========================================================= */

    .doc-title {
      font-size: 12pt;
      font-weight: bold;
      text-align: center;
      text-transform: uppercase;
      color: #000000;
      margin-top: 6px;
    }

    .doc-sub {
      font-size: 10pt;
      font-weight: bold;
      text-align: center;
      color: #000000;
      margin-bottom: 4px;
    }

    .doc-no {
      font-size: 8.5pt;
      text-align: center;
      color: #555555;
      margin-bottom: 4px;
    }

    .doc-co {
      font-size: 10pt;
      font-weight: bold;
      text-align: center;
      color: #000000;
      margin-bottom: 12px;
    }


    /* =========================================================
       KEY VALUE TABLE
       ========================================================= */

    .kv-table {
      width: 100%;
      border-collapse: collapse;
      margin: 4px 0;
    }

    .kv-table td {
      padding: 2px 3px;
      vertical-align: top;
      font-size: 8.5pt;
    }

    .kv-label {
      width: 120px;
      font-weight: bold;
    }

    .kv-colon {
      width: 12px;
    }

    .kv-value {
      width: auto;
    }


    /* =========================================================
       SIGNATURE TABLE
       ========================================================= */

    .sign-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 22px;
      page-break-inside: avoid;
    }

    .sign-table td {
      padding: 0 12px 0 0;
      vertical-align: top;
      text-align: right;
    }

    /*
     * Tetap rata kanan.
     * Tidak menggunakan fixed width / center alignment
     * agar mengikuti pola signature pada Offering Letter.
     */
    .sign-block {
      text-align: right;
    }


    /* =========================================================
       SIGNATURE DATE
       ========================================================= */

    .sign-date {
      font-size: 8.5pt;
      line-height: 1.25;
      margin: 0 0 1px 0;
    }


    /* =========================================================
       GREETING
       ========================================================= */

    .sign-greeting {
      font-size: 8.5pt;
      line-height: 1.25;
      margin: 0 0 1px 0;
    }


    /* =========================================================
       COMPANY NAME
       ========================================================= */

    .sign-company {
      font-size: 8.5pt;
      font-weight: bold;
      line-height: 1.25;
      margin: 0;
    }


    /* =========================================================
       SIGNATURE IMAGE AREA
       ========================================================= */

    /*
     * Fixed height supaya posisi nama tidak berubah
     * apabila ukuran signature image berbeda.
     */
    .sign-image-area {
      height: 52px;
      margin: 0;
      padding: 0;
    }

    .sign-image-area img {
      display: inline-block;
      height: 48px;
      width: auto;
      max-width: 145px;
      margin: 0;
      padding: 0;
    }


    /* =========================================================
       SIGNATORY NAME
       ========================================================= */

    .sign-name {
      font-size: 8.5pt;
      font-weight: bold;
      line-height: 1.25;
      margin: 0;
    }


    /* =========================================================
       SIGNATORY POSITION
       ========================================================= */

    .sign-position {
      font-size: 8.5pt;
      line-height: 1.25;
      margin: 1px 0 0 0;
    }

  </style>
</head>

<body>

  @include('pdf.components.kop-surat')


  @php
    // =========================================================
    // 1:1 GAS exportSKTetapPDF()
    // =========================================================

    // SK number
    $skNumber = $extraData['sk_number']
                ?? $extraData['skNumber']
                ?? $extraData['evalId']
                ?? (
                    '001/HRD-PK/'
                    . ($company['code'] ?? 'MSI')
                    . '/I/'
                    . date('Y')
                );


    // Position
    $position = $extraData['job_position']
                ?? $extraData['jobPosition']
                ?? $employee->jobPositionLocation
                ?? $employee->jobPosition
                ?? 'Jabatan';


    // Department
    $department = $extraData['department']
                  ?? $employee->department
                  ?? 'Departemen';


    // Division
    $division = $extraData['division']
                ?? $employee->division
                ?? 'Divisi';


    // Full name
    $fullName = $employee->fullName ?? '-';


    // Company
    $companyName = $company['name']
                   ?? 'PT MAHAKARYA SUKSES INDONESIA';


    // =========================================================
    // COMPANY CITY
    // =========================================================

    $companyAddress = $company['address'] ?? '';

    preg_match(
      '/Kota\s+([\w\s]+?)(?:,|$)/i',
      $companyAddress,
      $cityMatch
    );

    $city = isset($cityMatch[1])
            ? trim($cityMatch[1])
            : ($company['city'] ?? 'Jakarta');


    // =========================================================
    // INDONESIAN DATE
    // =========================================================

    $bulanId = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember'
    ];

    $todayStr =
      date('j')
      . ' '
      . $bulanId[(int) date('n') - 1]
      . ' '
      . date('Y');
  @endphp


  {{-- =========================================================
       JUDUL
       ========================================================= --}}

  <div class="doc-title">
    SURAT KEPUTUSAN
  </div>

  <div class="doc-no">
    Nomor: {{ $skNumber }}
  </div>

  <div class="doc-co">
    Perusahaan {{ $companyName }}
  </div>


  {{-- =========================================================
       MENIMBANG
       ========================================================= --}}

  <table class="kv-table">
    <tr>

      <td class="kv-label">
        Menimbang
      </td>

      <td class="kv-colon">
        :
      </td>

      <td class="kv-value">
        Bahwa berdasarkan hasil evaluasi masa percobaan serta
        kebutuhan Perusahaan, dipandang perlu mengangkat karyawan
        sebagai Karyawan Tetap sesuai dengan Peraturan Perusahaan
        dan ketentuan peraturan perundang-undangan yang berlaku.
      </td>

    </tr>
  </table>


  {{-- =========================================================
       MENGINGAT
       ========================================================= --}}

  <table class="kv-table">

    <tr>

      <td class="kv-label">
        Mengingat
      </td>

      <td class="kv-colon">
        :
      </td>

      <td class="kv-value">
        1. Undang-Undang Ketenagakerjaan beserta
        peraturan pelaksanaannya.
      </td>

    </tr>

    <tr>

      <td class="kv-label"></td>

      <td class="kv-colon"></td>

      <td class="kv-value">
        2. Peraturan Perusahaan {{ $companyName }}.
      </td>

    </tr>

    <tr>

      <td class="kv-label"></td>

      <td class="kv-colon"></td>

      <td class="kv-value">
        3. Hasil evaluasi kinerja selama masa percobaan.
      </td>

    </tr>

  </table>


  {{-- =========================================================
       MEMUTUSKAN
       ========================================================= --}}

  <div style="
    text-align: center;
    font-size: 10pt;
    font-weight: bold;
    margin: 12px 0 6px;
  ">
    MEMUTUSKAN
  </div>


  {{-- =========================================================
       MENETAPKAN
       ========================================================= --}}

  <table class="kv-table">

    <tr>

      <td class="kv-label">
        Menetapkan
      </td>

      <td class="kv-colon">
        :
      </td>

      <td class="kv-value"></td>

    </tr>

    <tr>

      <td class="kv-label"></td>

      <td class="kv-colon">
        :
      </td>

      <td class="kv-value">
        Mengangkat
        <strong>{{ $fullName }}</strong>
        sebagai Karyawan Tetap dengan jabatan
        {{ $position }},
        Departemen {{ $department }},
        Divisi {{ $division }},
        terhitung sejak surat keputusan ini ditetapkan
        dan ditandatangani.
      </td>

    </tr>

  </table>


  {{-- =========================================================
       PENUTUP
       ========================================================= --}}

  <p style="
    font-size: 8.5pt;
    margin-top: 16px;
  ">
    Demikian Surat Keputusan ini dibuat, untuk dilaksanakan
    sesuai Peraturan Perusahaan yang berlaku.
  </p>


  {{-- =========================================================
       TANDA TANGAN
       ========================================================= --}}

  <table class="sign-table">

    <tr>

      <td>

        <div class="sign-block">

          {{-- Tanggal --}}

          <div class="sign-date">
            {{ $city }}, {{ $todayStr }}
          </div>


          {{-- Hormat Kami --}}

          <div class="sign-greeting">
            Hormat Kami,
          </div>


          {{-- Nama Perusahaan --}}

          <div class="sign-company">
            <strong>{{ $companyName }}</strong>
          </div>


          {{-- Tanda Tangan --}}

          <div class="sign-image-area">
            @include('pdf.components.hr-sign')
          </div>


          {{-- Nama Penandatangan --}}

          <div class="sign-name">
            <strong>
              <u>Hisar Hesti</u>
            </strong>
          </div>


          {{-- Jabatan --}}

          <div class="sign-position">
            Human Resources (HR) &amp; Legal Manager
          </div>

        </div>

      </td>

    </tr>

  </table>

</body>
</html>