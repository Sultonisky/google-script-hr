<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan — Outsource TAD</title>
    <style>
        @page {
            margin: 36px 52px 64px 52px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.55;
            text-align: justify;
        }

        .doc-title-wrap {
            text-align: center;
            margin-bottom: 20px;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0;
        }

        p {
            margin: 8px 0;
            text-align: justify;
        }

        .pihak-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 12px;
        }

        .pihak-table td {
            padding: 2px;
            vertical-align: top;
            font-size: 11pt;
        }

        .pt-label {
            width: 80px;
        }

        .pt-colon {
            width: 12px;
        }

        ol.pasal-list {
            margin: 6px 0;
            padding-left: 22px;
        }

        ol.pasal-list>li {
            margin-bottom: 6px;
            text-align: justify;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
            page-break-inside: avoid;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 8px;
        }

        .sign-label {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .sign-materai {
            font-style: italic;
            font-size: 10pt;
            margin: 36px 0;
        }

        .sign-line {
            margin-top: 8px;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    @php
        $subject = $employee ?? null;
        $fullName = $subject?->fullName ?? '-';
        $address = $subject?->residentialAddress ?? ($subject?->citizenIdAddress ?? '-');

        $bulanId = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $rawDoc = $extraData['doc_date'] ?? ($extraData['docDate'] ?? null);
        $docObj = $rawDoc ? \Carbon\Carbon::parse($rawDoc) : now()->timezone('Asia/Jakarta');
        $docDateFmt = $docObj->day . ' ' . $bulanId[$docObj->month - 1] . ' ' . $docObj->year;
    @endphp

    <div class="doc-title-wrap">
        <div class="doc-title">Surat Pernyataan</div>
    </div>

    <p>Saya yang bertanda tangan dibawah ini :</p>
    <table class="pihak-table">
        <tr>
            <td class="pt-label">Nama</td>
            <td class="pt-colon">:</td>
            <td class="pt-value"><strong>{{ $fullName }}</strong></td>
        </tr>
        <tr>
            <td class="pt-label">Alamat</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $address }}</td>
        </tr>
    </table>

    <p>Dengan ini menyatakan bahwa:</p>
    <ol class="pasal-list">
        <li>Bahwa Surat Pernyataan ini saya tandatangani sebagai persyaratan yang harus dipenuhi agar saya dapat
            diterima bekerja di perusahaan rekanan dimana saya ditempatkan.</li>
        <li>Bahwa Jika dikemudian hari saya diterima bekerja saya akan mematuhi dan mentaati perjanjian kerja, peraturan
            perusahaan yang berlaku diperusahaan dimana saya ditempatkan dan tidak akan mengikuti, melakukan atau
            mengajak teman sekerja untuk melakukan perbuatan-perbuatan, tindakan-tindakan baik sendiri-sendiri maupun
            secara bersama-sama yang dapat merugikan perusahaan rekanan dimana saya ditempatkan bekerja.</li>
        <li>Bahwa saya tidak akan melakukan tuntutan untuk diterima sebagai karyawan tetap di perusahaan rekanan dimana
            saya ditempatkan bekerja.</li>
        <li>Bahwa saya akan mengikuti aturan upah yang diberikan oleh perusahaan rekanan dimana saya ditempatkan sesuai
            dengan kesepakatan bersama.</li>
        <li>Bahwa saya menyatakan dengan sebenar-benarnya bahwa selama menjalankan tugas dan bekerja pada perusahaan
            pemberi kerja, saya <strong>tidak akan mengajak, memengaruhi, atau mendorong rekan kerja maupun diri saya
                sendiri untuk membentuk, mendirikan, atau mengikuti organisasi/serikat pekerja</strong> yang dapat
            mengganggu hubungan kerja atau kepentingan perusahaan, tanpa mengikuti ketentuan dan prosedur yang berlaku.
        </li>
        <li>Apabila di kemudian hari saya terbukti melanggar ketentuan atau <strong>Kode Etik</strong> yang telah saya
            tanda tangani, maka saya bersedia menerima <strong>sanksi sesuai dengan peraturan perundang-undangan,
                peraturan perusahaan, dan ketentuan yang berlaku</strong>.</li>
        <li>Saya juga bersedia mempertanggungjawabkan setiap pelanggaran yang saya lakukan dan menerima sanksi
            administratif atau sanksi lainnya sesuai ketentuan yang berlaku.</li>
        <li>Bahwa apabila saya mengingkari pernyataan ini yang merupakan persyaratan untuk dapat saya bekerja, maka saya
            bersedia untuk dituntut sesuai dengan ketentuan hukum yang berlaku.</li>
    </ol>

    <p>Demikianlah surat pernyataan ini saya tanda tangani di Tangerang, pada tanggal <strong>{{ $docDateFmt }}</strong>
        dalam keadaan sadar, sehat jasmani dan rohani serta tanpa adanya unsur paksaan atau tekanan dari pihak manapun
        juga.</p>

    <table class="sign-table">
        <tr>
            <td></td>
            <td>
                <div>Tangerang, {{ $docDateFmt }}</div>
                <div class="sign-label" style="margin-top:8px">Yang menyatakan</div>
                <div class="sign-materai">Materai 10.000</div>
                <div class="sign-line">({{ $fullName }})</div>
            </td>
        </tr>
    </table>
</body>

</html>
