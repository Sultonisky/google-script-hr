<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Pakta Integritas - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 36px 48px 40px 48px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000000;
            line-height: 1.45;
            text-align: justify;
            margin: 0;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 2px;
        }

        .doc-subtitle {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 16px;
        }

        p {
            margin: 6px 0;
        }

        .identity {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 10px;
        }

        .identity td {
            padding: 1px 2px;
            vertical-align: top;
            font-size: 12pt;
        }

        .identity .label {
            width: 58px;
        }

        .identity .colon {
            width: 12px;
        }

        ol.clauses {
            margin: 6px 0 8px;
            padding-left: 22px;
        }

        ol.clauses > li {
            margin-bottom: 6px;
            text-align: justify;
        }

        .closing {
            page-break-inside: avoid;
        }

        .sign-block {
            width: 240px;
            margin-left: auto;
            margin-top: 18px;
            text-align: center;
            page-break-inside: avoid;
        }

        .sign-space {
            height: 72px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    @php
        $bulanId = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $legalName = trim((string) ($company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA'));
        $companyName = preg_match('/^PT\s+(.+)$/i', $legalName, $companyMatch)
            ? 'PT ' . mb_convert_case(mb_strtolower($companyMatch[1]), MB_CASE_TITLE, 'UTF-8')
            : $legalName;

        $fullName = trim((string) ($employee->fullName ?? ''));
        if ($fullName === '') {
            $fullName = '-';
        }

        $nik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'");

        $rawDoc = trim((string) ($extraData['doc_date'] ?? ($extraData['docDate'] ?? '')));
        $docObj = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDoc)) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('Y-m-d', $rawDoc, 'Asia/Jakarta');
                if ($parsed !== false && $parsed->format('Y-m-d') === $rawDoc) {
                    $docObj = $parsed;
                }
            } catch (\Throwable) {
                $docObj = null;
            }
        }
        $docDateFmt = $docObj
            ? $docObj->format('d') . ' ' . $bulanId[$docObj->month - 1] . ' ' . $docObj->year
            : '-';
        $docCity = trim((string) ($company['city'] ?? 'Jakarta'));
        if ($docCity === '') {
            $docCity = 'Jakarta';
        }
    @endphp

    <div class="doc-title">SURAT PAKTA INTEGRITAS</div>
    <div class="doc-subtitle">PERNYATAAN KOMITMEN DAN TANGGUNG JAWAB</div>

    <p>Yang bertanda tangan di bawah ini:</p>

    <table class="identity">
        <tr>
            <td class="label"><strong>Nama</strong></td>
            <td class="colon">:</td>
            <td><strong>{{ $fullName }}</strong></td>
        </tr>
        <tr>
            <td class="label"><strong>NIK</strong></td>
            <td class="colon">:</td>
            <td><strong>{{ $nik }}</strong></td>
        </tr>
    </table>

    <p>Sebagai karyawan dari <strong>{{ $companyName }}</strong> (selanjutnya disebut Perusahaan) dengan ini menyatakan berkomitmen bahwa:</p>

    <ol class="clauses">
        <li><strong>Berkomitmen</strong> melaksanakan tugas dan tanggung jawab pekerjaan secara profesional serta mematuhi Standar Operasional Prosedur (SOP), Peraturan Perusahaan, dan kebijakan internal Perusahaan.</li>
        <li><strong>Berkomitmen</strong> tidak melakukan pungutan liar (PUNGLI), tidak meminta atau menerima gratifikasi, hadiah, komisi, atau imbalan dalam bentuk apa pun, baik secara langsung maupun tidak langsung, dari pihak mana pun yang berhubungan dengan jabatan dan/atau pekerjaan saya di Perusahaan.</li>
        <li><strong>Berkomitmen</strong> tidak melakukan praktik Korupsi, Kolusi, dan Nepotisme (KKN) dalam bentuk apa pun, baik yang menguntungkan diri sendiri, keluarga, kelompok, maupun pihak lain, yang dapat merugikan Perusahaan.</li>
        <li><strong>Berkomitmen</strong> tidak melakukan pekerjaan, kegiatan usaha, atau proyek di luar Perusahaan yang menimbulkan benturan kepentingan (conflict of interest) dan/atau mengganggu pelaksanaan tugas, tanggung jawab, serta kinerja saya sebagai tenaga kerja.</li>
        <li><strong>Berkomitmen</strong> menjaga kehormatan, loyalitas, disiplin, integritas, serta nama baik Perusahaan.</li>
        <li><strong>Berkomitmen</strong> untuk bersedia ditempatkan dan/atau dimutasikan ke lokasi atau wilayah kerja sesuai dengan kebutuhan operasional dan kebijakan Perusahaan.</li>
        <li><strong>Berkomitmen</strong> menjaga kerahasiaan seluruh data, informasi, dan dokumen milik Perusahaan yang diketahui atau diperoleh selama hubungan kerja serta tidak membocorkan, menyebarluaskan, atau menggunakannya untuk kepentingan pribadi maupun pihak lain, baik selama maupun setelah berakhirnya hubungan kerja, kecuali ditentukan lain berdasarkan ketentuan peraturan perundang-undangan.</li>
        <li><strong>Berkomitmen</strong> tidak mengajak, membujuk, mempengaruhi, atau mendorong pihak ketiga yang memiliki hubungan kerja sama dengan Perusahaan, termasuk namun tidak terbatas pada pelanggan, distributor, pemasok, mitra usaha, maupun tenaga kerja lainnya, untuk mengakhiri hubungan kerja sama, melakukan pelanggaran kontrak, atau melakukan tindakan yang dapat merugikan kepentingan Perusahaan.</li>
        <li><strong>Berkomitmen</strong> bertanggung jawab atas setiap pelanggaran yang saya lakukan sesuai dengan ketentuan peraturan perundang-undangan, Peraturan Perusahaan, Perjanjian Kerja, dan kebijakan Perusahaan yang berlaku.</li>
        <li><strong>Berkomitmen</strong> untuk memahami dan menerima bahwa Perjanjian Kerja Waktu Tertentu (PKWT) akan berakhir sesuai dengan jangka waktu yang telah diperjanjikan. Dengan kesadaran, kemauan, dan kehendak sendiri serta tanpa adanya paksaan, tekanan, atau pengaruh dari pihak mana pun, saya menyatakan secara sukarela tidak menerima pembayaran uang kompensasi atas berakhirnya PKWT tersebut.</li>
        <li><strong>Berkomitmen</strong> mematuhi seluruh ketentuan peraturan perundang-undangan, Peraturan Perusahaan, Perjanjian Kerja, serta kebijakan Perusahaan selama hubungan kerja berlangsung.</li>
    </ol>

    <div class="closing">
        <p>Demikian Surat Pakta Integritas ini saya buat dengan sebenar-benarnya, dalam keadaan sadar, tanpa adanya paksaan dari pihak mana pun, sebagai bentuk komitmen saya untuk mematuhi seluruh ketentuan yang berlaku di Perusahaan.</p>

        <div class="sign-block">
            <div><strong>{{ $docCity }}, {{ $docDateFmt }}</strong></div>
            <div>Yang menyatakan,</div>
            <div class="sign-space"></div>
            <div class="sign-name">{{ $fullName }}</div>
        </div>
    </div>
</body>

</html>
