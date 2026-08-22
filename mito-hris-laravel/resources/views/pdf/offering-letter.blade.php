```html id="n2k7qa"
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Offering Letter - {{ $candidate->fullName }}</title>

    <style>
        @page {
            margin: 25px 35px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            line-height: 1.55;
            text-align: justify;
        }


        /* =========================================================
       DOCUMENT HEADER
       ========================================================= */

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            color: #0b2540;
            margin-top: 10px;
        }

        .doc-no {
            font-size: 9.5pt;
            text-align: center;
            color: #4b5563;
            margin-bottom: 14px;
        }


        /* =========================================================
       GENERAL TABLE
       ========================================================= */

        .pihak-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 8px;
        }

        .pihak-table td {
            padding: 2px 3px;
            vertical-align: top;
        }

        .pihak-label {
            width: 30%;
            font-weight: bold;
        }

        .pihak-colon {
            width: 3%;
        }

        .pihak-value {
            width: 67%;
        }


        /* =========================================================
       SECTION
       ========================================================= */

        .section-title {
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 4px;
        }

        ul {
            margin-top: 4px;
            margin-bottom: 6px;
            padding-left: 20px;
        }

        li {
            margin-bottom: 2px;
        }


        /* =========================================================
       SIGNATURE SECTION
       ========================================================= */

        .signature-section {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }


        /* =========================================================
       HR SIGNATURE
       ========================================================= */

        .hr-signature-block {
            width: 100%;
            text-align: left;
            font-size: 9px;
        }

        .signature-date {
            font-size: 9px;
            margin-bottom: 2px;
        }

        .signature-greeting {
            font-size: 9px;
            margin-bottom: 1px;
        }

        .signature-company {
            font-size: 9px;
            margin-bottom: 1px;
        }


        /* HR signature image area */

        .hr-signature-area {
            width: 155px;
            height: 52px;
            margin: 0;
        }

        .hr-sign-image {
            display: block;
            height: 48px;
            width: auto;
            max-width: 145px;
        }

        .hr-sign-placeholder {
            height: 48px;
            width: 120px;
        }


        .hr-name {
            font-size: 9px;
            line-height: 1.2;
        }

        .hr-position {
            font-size: 8.5px;
            color: #555555;
            margin-top: 1px;
        }


        /* =========================================================
       CANDIDATE SIGNATURE
       ========================================================= */

        .candidate-signature-block {
            width: 100%;
            margin-top: 22px;
            text-align: left;
            font-size: 9px;
        }


        /* Candidate confirmation text */

        .candidate-confirmation {
            width: 100%;
            font-size: 8.8px;
            line-height: 1.45;
            text-align: justify;
            margin-bottom: 3px;
        }

        .candidate-confirmation p {
            margin: 0 0 5px 0;
        }


        /* Join date */

        .join-date-line {
            white-space: nowrap;
            display: inline-block;
            margin-left: 2px;
        }


        /* Candidate signature area */

        .candidate-signature-area {
            height: 38px;
            width: 100%;
        }


        /* Candidate signature line */

        .candidate-line {
            width: 200px;
            border-bottom: 1px solid #222222;
            margin-bottom: 3px;
        }


        .candidate-name {
            font-size: 9px;
            line-height: 1.2;
        }

        .candidate-role {
            font-size: 8px;
            color: #777777;
            margin-top: 1px;
        }
    </style>
</head>


<body>

    @include('pdf.components.kop-surat')


    @php

        /*
    |--------------------------------------------------------------------------
    | Candidate Data
    |--------------------------------------------------------------------------
    */

        $candName = $candidate->fullName ?? '-';

        $candNik = ltrim($candidate->nik ?? '-', "'");

        $candEmail = $candidate->email ?? '-';

        $candPhone = ltrim($candidate->phone ?? '-', "'");

        $candCity = $candidate->city ?? '-';

        $candAddr = $candidate->address ?? '-';

        /*
    |--------------------------------------------------------------------------
    | Job Information
    |--------------------------------------------------------------------------
    */

        $pos = $extraData['position'] ?? ($candidate->positionApplied ?? '-');

        $division = $extraData['division'] ?? '-';

        $lokasi = $extraData['lokasi_kerja'] ?? ($extraData['lokasiKerja'] ?? $candCity);

        $dept = $extraData['department'] ?? '-';

        $jobLevel = $extraData['job_level'] ?? ($extraData['jobLevel'] ?? '');

        $combinedPos = $pos . ($jobLevel ? ' ' . $jobLevel : '');

        /*
    |--------------------------------------------------------------------------
    | Employment
    |--------------------------------------------------------------------------
    */

        $empStatus =
            $extraData['employment_status'] ?? ($extraData['employmentStatus'] ?? 'Perjanjian Kerja Waktu Tertentu');

        $contractDur = $extraData['contract_duration'] ?? ($extraData['contractDuration'] ?? '12 bulan');

        /*
    |--------------------------------------------------------------------------
    | Salary
    |--------------------------------------------------------------------------
    */

        $baseSalary = (int) preg_replace(
            '/[^0-9]/',
            '',
            (string) ($extraData['salary_basic'] ?? ($extraData['salaryBasic'] ?? ($candidate->expectedSalary ?? 0))),
        );

        $allowPulsa = (int) preg_replace(
            '/[^0-9]/',
            '',
            (string) ($extraData['allow_pulsa'] ?? ($extraData['allowPulsa'] ?? 100000)),
        );

        $allowTransport = (int) preg_replace(
            '/[^0-9]/',
            '',
            (string) ($extraData['allow_transport'] ?? ($extraData['allowTransport'] ?? 500000)),
        );

        $totalBruto = $baseSalary + $allowPulsa + $allowTransport;

        /*
    |--------------------------------------------------------------------------
    | Other Data
    |--------------------------------------------------------------------------
    */

        $workingHours =
            $extraData['working_hours'] ??
            ($extraData['workingHours'] ?? 'hari Senin – Jumat mulai pukul 08.00 – 17.00 WIB');

        $benefit = $extraData['benefit'] ?? '';

        $joinDate = $extraData['join_date'] ?? ($extraData['joinDate'] ?? '');

        $joinDateFmt = $joinDate ? \Carbon\Carbon::parse($joinDate)->translatedFormat('d F Y') : date('d F Y');

        /*
    |--------------------------------------------------------------------------
    | Offer Expiration
    |--------------------------------------------------------------------------
    */

        $expireDate = date('d F Y', strtotime('+3 days'));

        /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */

        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';

        $companyCity = $company['city'] ?? 'Tangerang';

        $todayFmt = date('d F Y');
    @endphp


    <!-- =========================================================
       DATE
       ========================================================= -->

    <div style="
      text-align:right;
      font-size:9.5px;
      color:#4b5563;
      margin-bottom:8px;
  ">
        Tanggal:
        <strong>{{ $todayFmt }}</strong>
    </div>


    <!-- =========================================================
       RECIPIENT
       ========================================================= -->

    <p>
        Kepada Yth. <strong>{{ $candName }}</strong><br>
        Perihal:
        <strong>Penawaran Kerja (Offering Letter)</strong>
    </p>


    <p>
        Dengan hormat,
    </p>


    <p>
        Berdasarkan hasil proses seleksi yang telah Saudara ikuti,
        kami dengan senang hati menyampaikan penawaran kerja untuk
        bergabung bersama <strong>{{ $companyName }}</strong>
        dengan ketentuan sebagai berikut:
    </p>


    <!-- =========================================================
       JOB INFORMATION
       ========================================================= -->

    <table class="pihak-table">

        <tr>
            <td class="pihak-label">
                Posisi Jabatan
            </td>

            <td class="pihak-colon">
                :
            </td>

            <td class="pihak-value">
                <strong>{{ $combinedPos }}</strong>
            </td>
        </tr>


        <tr>
            <td class="pihak-label">
                Divisi
            </td>

            <td class="pihak-colon">
                :
            </td>

            <td class="pihak-value">
                {{ $division }}
            </td>
        </tr>


        <tr>
            <td class="pihak-label">
                Lokasi Penempatan
            </td>

            <td class="pihak-colon">
                :
            </td>

            <td class="pihak-value">
                {{ $lokasi }}
            </td>
        </tr>


        <tr>
            <td class="pihak-label">
                Status Hubungan Kerja
            </td>

            <td class="pihak-colon">
                :
            </td>

            <td class="pihak-value">
                {{ $empStatus }}
            </td>
        </tr>

    </table>


    <p>
        Dengan masa kontrak selama
        <strong>{{ $contractDur }}</strong>
        sesuai ketentuan perusahaan dan peraturan
        perundang-undangan yang berlaku.
    </p>


    <!-- =========================================================
       REMUNERATION
       ========================================================= -->

    <p>
        Perusahaan menawarkan paket remunerasi sebagai berikut:
    </p>


    <table style="
      width:55%;
      border-collapse:collapse;
      margin:8px 0;
  ">

        <tr>

            <td style="
          font-weight:bold;
          padding:2px 4px 2px 0;
      ">
                Komponen
            </td>

            <td
                style="
          font-weight:bold;
          padding:2px 0;
          text-align:left;
          padding-left:16px;
      ">
                Nilai
            </td>

        </tr>


        <tr>

            <td style="
          padding:3px 4px 3px 0;
      ">
                Gaji Pokok
            </td>

            <td style="
          padding:3px 0;
          text-align:left;
          padding-left:16px;
      ">
                Rp {{ number_format($baseSalary, 0, ',', '.') }},-
            </td>

        </tr>


        <tr>

            <td style="
          padding:3px 4px 3px 0;
      ">
                Tunjangan Variabel (Pulsa)
            </td>

            <td style="
          padding:3px 0;
          text-align:left;
          padding-left:16px;
      ">
                Rp {{ number_format($allowPulsa, 0, ',', '.') }},-
            </td>

        </tr>


        <tr>

            <td style="
          padding:3px 4px 3px 0;
      ">
                Tunjangan Variabel (Transport)
            </td>

            <td style="
          padding:3px 0;
          text-align:left;
          padding-left:16px;
      ">
                Rp {{ number_format($allowTransport, 0, ',', '.') }},-
            </td>

        </tr>


        <tr>

            <td style="
          padding:3px 4px 3px 0;
          font-weight:bold;
      ">
                Total Penghasilan Bruto / Bulan
            </td>

            <td
                style="
          padding:3px 0;
          text-align:left;
          padding-left:16px;
          font-weight:bold;
      ">
                Rp {{ number_format($totalBruto, 0, ',', '.') }},-
            </td>

        </tr>

    </table>


    <!-- =========================================================
       SALARY INFORMATION
       ========================================================= -->

    <p>
        Kompensasi tersebut akan dikenakan potongan BPJS sesuai
        ketentuan perundangan yang berlaku. Perusahaan akan membayar
        PPH atas gaji saudara dan membayarkannya kepada Dirjen Pajak.
        Gaji akan dihitung mulai tanggal 21 hingga tanggal 20 bulan
        berikutnya dan akan langsung dibayarkan ke rekening bank
        Saudara yang telah didaftarkan ke Perusahaan pada tanggal
        30 atau 31 di akhir bulan.
    </p>


    <p>
        Jam kerja Saudara di {{ $workingHours }}.
    </p>


    <!-- =========================================================
       BENEFITS
       ========================================================= -->

    <div class="section-title">
        Selain kompensasi di atas, Saudara akan memperoleh
        fasilitas sebagai berikut:
    </div>


    <ul>

        <li>
            BPJS Kesehatan
        </li>

        <li>
            BPJS Ketenagakerjaan
        </li>

        <li>
            Tunjangan Hari Raya (THR)
        </li>

        <li>
            Cuti Tahunan sesuai kebijakan perusahaan
        </li>

        <li>
            Biaya Operasional yakni biaya bensin, tol, parkir saat
            melakukan perjalanan bisnis dapat di reimburse sesuai
            ketentuan Perusahaan.
        </li>

        @if ($benefit)
            <li>
                {{ $benefit }}
            </li>
        @endif

    </ul>


    <!-- =========================================================
       OTHER TERMS
       ========================================================= -->

    <p>
        Saudara berhak mengikuti program insentif dan/atau bonus
        perusahaan sesuai pencapaian KPI, kinerja perusahaan,
        serta kebijakan yang berlaku.
    </p>


    <p>
        Saudara wajib menjaga kerahasiaan seluruh informasi,
        data, strategi bisnis, maupun dokumen perusahaan yang
        diperoleh selama masa kerja dan setelah hubungan kerja
        berakhir.
    </p>


    <p>
        Penawaran kerja ini berlaku sampai dengan tanggal
        <strong>{{ $expireDate }}</strong>.
    </p>


    <p>
        Apabila Saudara menyetujui penawaran ini, mohon
        menandatangani dokumen ini dan mengembalikannya kepada
        kami sebelum batas waktu tersebut.
    </p>


    <p>
        Kami berharap Saudara dapat bergabung dan berkontribusi
        bersama <strong>{{ $companyName }}</strong> dalam mencapai
        tujuan dan pertumbuhan perusahaan.
    </p>


    <!-- =========================================================
       SIGNATURE
       ========================================================= -->

    @php

        $hrSignPath = public_path('assets/hr-sign.png');

        $hrSignSrc = file_exists($hrSignPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($hrSignPath))
            : null;

    @endphp


    <div class="signature-section">


        <!-- =======================================================
         HR SIGNATURE
         ======================================================= -->

        <div class="hr-signature-block">

            <div class="signature-date">
                {{ $companyCity }}, {{ $todayFmt }}
            </div>

            <div class="signature-greeting">
                Hormat kami,
            </div>

            <div class="signature-company">
                <strong>{{ $companyName }}</strong>
            </div>


            <!-- HR SIGNATURE IMAGE -->

            <div class="hr-signature-area">

                @if ($hrSignSrc)
                    <img src="{{ $hrSignSrc }}" class="hr-sign-image" alt="Tanda tangan HR">
                @else
                    <div class="hr-sign-placeholder"></div>
                @endif

            </div>


            <div class="hr-name">
                <strong>
                    <u>Hisar Hesti Pangaribuan</u>
                </strong>
            </div>


            <div class="hr-position">
                Human Resource &amp; Legal Manager
            </div>

        </div>


        <!-- =======================================================
         CANDIDATE SIGNATURE
         ======================================================= -->

        <div class="candidate-signature-block">


            <!-- Confirmation -->

            <div class="candidate-confirmation">

                <p>
                    Dengan ini saya memberikan konfirmasi bahwa saya telah
                    membaca, memahami, dan menyetujui semua persyaratan surat
                    penawaran ini dan saya menerima penawaran ini sebagaimana
                    disajikan.
                </p>


                <p>
                    Saya bersedia bergabung di Perusahaan
                    <strong>{{ $companyName }}</strong>
                    pada tanggal:

                    <span class="join-date-line">
                        __________________
                    </span>
                </p>

            </div>


            <!-- Candidate Signature Space -->

            <div class="candidate-signature-area">
                &nbsp;
            </div>


            <!-- Candidate Signature Line -->

            <div class="candidate-line"></div>


            <!-- Candidate Name -->

            <div class="candidate-name">
                <strong>
                    {{ $candName }}
                </strong>
            </div>


            <!-- Candidate Role -->

            <div class="candidate-role">
                Kandidat
            </div>

        </div>

    </div>

</body>

</html>
```
