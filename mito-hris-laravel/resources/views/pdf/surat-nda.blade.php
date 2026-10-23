<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan NDA - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 32px 48px 22px 48px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000000;
            line-height: 1.28;
            text-align: justify;
            margin: 0;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 12px;
        }

        p {
            margin: 9px 0;
        }

        .identity {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 12px;
        }

        .identity td {
            padding: 2px 2px;
            vertical-align: top;
            font-size: 12pt;
        }

        .identity .label {
            width: 72px;
        }

        .identity .colon {
            width: 12px;
        }

        .sign-block {
            width: 260px;
            margin-top: 12px;
            text-align: left;
            page-break-inside: avoid;
        }

        .sign-space {
            height: 92px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .witness {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .witness table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .witness td {
            width: 50%;
            vertical-align: top;
            padding: 2px 12px 2px 0;
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
        $companyName = preg_match('/^PT\.?\s+(.+)$/i', $legalName, $companyMatch)
            ? 'PT. ' . mb_convert_case(mb_strtolower($companyMatch[1]), MB_CASE_TITLE, 'UTF-8')
            : $legalName;

        $fullName = trim((string) ($employee->fullName ?? ''));
        if ($fullName === '') {
            $fullName = '-';
        }

        $nik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'");
        $address = trim((string) ($employee->citizenIdAddress ?: $employee->residentialAddress));
        $position = trim((string) ($employee->jobPosition ?: $employee->jobPositionLocation));

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
            ? $docObj->day . ' ' . $bulanId[$docObj->month - 1] . ' ' . $docObj->year
            : '-';
        $docCity = trim((string) ($company['city'] ?? 'Jakarta'));
        if ($docCity === '') {
            $docCity = 'Jakarta';
        }
    @endphp

    <div class="doc-title">SURAT PERNYATAAN NON-DISCLOSURE AGREEMENT</div>

    <p>Saya yang bertandatangan di bawah ini :</p>

    <table class="identity">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td>{{ $fullName }}</td>
        </tr>
        <tr>
            <td class="label">No KTP</td>
            <td class="colon">:</td>
            <td>{{ $nik }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td>{{ $address }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td>{{ $position }}</td>
        </tr>
    </table>

    <p>Dengan ini saya menyatakan dan berjanji dengan sungguh – sungguh bahwa saya tidak akan membocorkan/memberitahukan/mengajarkan kepada pihak manapun juga mengenai ilmu atau informasi yang saya peroleh selama bekerja di {{ $companyName }}. Ilmu atau informasi tersebut merupakan Informasi rahasia milik {{ $companyName }} yang mana informasi rahasia yang tidak diketahui oleh pihak – pihak diluar kepentingan {{ $companyName }} termasuk, namun tidak terbatas pada, aplikasi paten, informasi yang berkaitan dengan inovasi dan penemuan baru, produk, perhitungan, design sistem, sistem informasi, program komputer, rencana marketing dan bisnis, pembiayaan, penentuan harga, surat-surat perjanjian, struktur organisasi atau informasi lain yang berkaitan dengan bisnis atau potensi bisnis {{ $companyName }}.</p>

    <p>Saya juga memahami bahwa informasi rahasia merupakan rahasia dagang dari {{ $companyName }}, maka saya menyatakan untuk tidak menggunakan informasi rahasia yang dimaksud untuk menguntungkan pihak lain, Perusahaan maupun entitas lain.</p>

    <p>Saya akan melaporkan semua tulisan, inovasi, penemuan dan ide yang dibuat, dipelajari atau dilaksanakan sendiri maupun bersama – sama dengan rekan lainnya, baik yang berpotensi dipatenkan, didaftarkan hak cipta atau status lain yang sejenis maupun tidak, kepada {{ $companyName }} secara tertulis selama periode kerja dengan {{ $companyName }}, termasuk melaporkan semua catatan, memorandum, disk komputer, dan alat lain yang memiliki fungsi yang sama sebagai penyimpan informasi rahasia menjadi milik dari {{ $companyName }}. Saya menyetujui bahwa semua penemuan yang dimaksud (intelektual, visual maupun material) merupakan milik {{ $companyName }}.</p>

    <p>Jika selama bekerja terdapat informasi yang dibocorkan oleh saya, maka saya siap menerima konsekuensi berupa pemecatan secara tidak terhormat. Namun jika setelah saya tidak bekerja lagi di {{ $companyName }}, saya juga akan bekerjasama untuk dihubungi serta menyelesaikan semua permasalahan yang mungkin terjadi selama permasalahan tersebut masih berhubungan dengan pekerjaan saya di {{ $companyName }}. Dan saya juga akan bersedia diproses secara hukum, jika ditemukan dikemudian hari ada informasi yang mana milik {{ $companyName }} telah dibocorkan oleh saya selaku mantan karyawan {{ $companyName }}.</p>

    <div class="closing">
        <p>Demikian surat pernyataan ini saya buat tanpa paksaan dan tekanan dari pihak manapun.</p>

        <div class="sign-block">
            <div>{{ $docCity }}, {{ $docDateFmt }}</div>
            <div>Yang Membuat Pernyataan,</div>
            <div class="sign-space"></div>
            <div class="sign-name">{{ $fullName }}</div>
        </div>

        <div class="witness">
            <div>Saksi – saksi :</div>
            <table>
                <tr>
                    <td>1. Nama :</td>
                    <td>2. Nama :</td>
                </tr>
                <tr>
                    <td>Jabatan :</td>
                    <td>Jabatan :</td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
