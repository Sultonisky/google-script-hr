<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>PKWT Tenaga Alih Daya — PT Damarindo Mandiri</title>
    <style>
        @page {
            margin: 28px 46px 64px 46px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.5;
            text-align: justify;
        }

        /* Paraf tiap halaman: kiri HRD, kanan KARYAWAN */
        .footer-paraf {
            position: fixed;
            bottom: 8px;
            left: 46px;
            right: 46px;
            z-index: 2;
            font-size: 9pt;
        }

        .footer-paraf-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-paraf-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .footer-paraf-left {
            text-align: left;
        }

        .footer-paraf-right {
            text-align: right;
        }

        .paraf-label {
            font-size: 9pt;
            margin-bottom: 18px;
        }

        .paraf-line {
            display: block;
            width: 60px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }

        .paraf-line-right {
            display: inline-block;
            width: 60px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }

        .paraf-role {
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            margin-top: 2px;
        }

        .doc-title-wrap {
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0 8px;
        }

        .doc-number {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 4px;
        }

        p {
            margin: 6px 0;
            text-align: justify;
        }

        .pihak-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 8px;
        }

        .pihak-table td {
            padding: 1px 2px;
            vertical-align: top;
            font-size: 11pt;
        }

        .pt-label {
            width: 140px;
        }

        .pt-colon {
            width: 12px;
        }

        ol.pasal-list {
            margin: 4px 0 4px 0;
            padding-left: 22px;
        }

        ol.pasal-list>li {
            margin-bottom: 4px;
            text-align: justify;
        }

        ol.pasal-list.alpha {
            list-style-type: lower-alpha;
            padding-left: 20px;
            margin-top: 4px;
        }

        .pasal-heading {
            text-align: center;
            font-weight: bold;
            margin: 16px 0 6px;
            font-size: 11pt;
            text-transform: uppercase;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        /* Digunakan hanya untuk halaman tanda tangan agar selalu mulai bersih */
        .page-break-explicit {
            page-break-before: always;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
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

        .sign-company {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 8px;
        }

        .sign-materai {
            font-style: italic;
            font-size: 10pt;
            margin: 28px 0 28px;
        }

        .sign-line {
            margin-top: 8px;
            font-weight: bold;
            text-decoration: underline;
        }

        .sign-role {
            font-size: 10.5pt;
        }

        .hr-line {
            border: none;
            border-top: 1px solid #000;
            margin: 10px 0 12px;
        }

        /* Tutup footer paraf di halaman terakhir (halaman tanda tangan) */
        .last-page-footer-mask {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 52px;
            background: #ffffff;
            z-index: 10;
        }
    </style>
</head>

<body>
    {{-- Paraf fixed tiap halaman: kiri HRD, kanan KARYAWAN --}}
    <div class="footer-paraf">
        <table class="footer-paraf-table">
            <tr>
                <td class="footer-paraf-left">
                    <div class="paraf-label">Paraf</div>
                    <span class="paraf-line"></span>
                    <div class="paraf-role">HRD</div>
                </td>
                <td class="footer-paraf-right">
                    <div class="paraf-label">Paraf</div>
                    <span class="paraf-line-right"></span>
                    <div class="paraf-role">KARYAWAN</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $subject = $employee ?? null;
        $fullName = $subject?->fullName ?? '-';
        $birthPlace = $subject?->birthPlace ?? '-';
        $rawBirth = $subject?->birthDate ?? null;
        $address = $subject?->residentialAddress ?? ($subject?->citizenIdAddress ?? '-');
        $phone = ltrim((string) ($subject?->mobilePhone ?? '-'), "'");
        $email = $subject?->personalEmail ?: ($subject?->workingEmail ?? '-');

        $bulanId = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $fmtDate = function ($raw) use ($bulanId) {
            if (!$raw) {
                return '-';
            }
            try {
                $d = \Carbon\Carbon::parse($raw);
                return $d->day . ' ' . $bulanId[$d->month - 1] . ' ' . $d->year;
            } catch (\Throwable $e) {
                return (string) $raw;
            }
        };

        $birthFmt = $fmtDate($rawBirth);
        $ttl = trim($birthPlace . ($rawBirth ? ', ' . $birthFmt : ''));
        $pendidikan = $extraData['pendidikan'] ?? '-';
        $perusahaanPenempatan = $extraData['perusahaan'] ?? '-';
        $alamatPenempatan = $extraData['beralamat_di'] ?? ($extraData['beralamatDi'] ?? '-');

        $contractNo =
            $extraData['contract_number'] ??
            ($extraData['contractNumber'] ?? '001/DM-PKWT/TAD/' . date('Y'));

        $rawDoc = $extraData['doc_date'] ?? ($extraData['docDate'] ?? null);
        $docObj = $rawDoc ? \Carbon\Carbon::parse($rawDoc) : now()->timezone('Asia/Jakarta');
        $docDay = $dayNames[$docObj->dayOfWeek];
        $docDateFmt = $docObj->day . ' ' . $bulanId[$docObj->month - 1] . ' ' . $docObj->year;

        $rawMulai = $extraData['mulai_tanggal'] ?? ($extraData['mulaiTanggal'] ?? null);
        $mulaiFmt = $fmtDate($rawMulai);

        $damarindoAddr =
            'Ruko Sastra Plasa Blok A No. 06 Jalan Raya Gatot Subroto KM 5,4, Jatiuwung Kota Tangerang';
    @endphp

    {{-- ═══════════════════════════════════════════════════════
         HALAMAN 1 — JUDUL & PARA PIHAK
    ═══════════════════════════════════════════════════════ --}}
    <div class="doc-title-wrap">
        <div class="doc-title">Perjanjian Kerja Waktu Tertentu</div>
        <div class="doc-subtitle">( Tenaga Alih Daya )</div>
        <hr class="hr-line">
        <div class="doc-number">NOMOR : {{ $contractNo }}</div>
    </div>

    <p>Pada hari ini <strong>{{ $docDay }}</strong>, Tanggal <strong>{{ $docDateFmt }}</strong>, bertempat di Kantor
        PT. Damarindo Mandiri, yang bertanda tangan dibawah ini :</p>

    <p><strong>1.</strong></p>
    <table class="pihak-table">
        <tr>
            <td class="pt-label">Nama</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">Megawati dalam hal ini bertindak untuk dan atas nama <strong>PT. Damarindo
                    Mandiri</strong> yang beralamat di {{ $damarindoAddr }}.</td>
        </tr>
    </table>
    <p>Selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.</p>

    <p><strong>2.</strong></p>
    <table class="pihak-table">
        <tr>
            <td class="pt-label">Nama</td>
            <td class="pt-colon">:</td>
            <td class="pt-value"><strong>{{ $fullName }}</strong></td>
        </tr>
        <tr>
            <td class="pt-label">Tempat &amp; Tgl Lahir</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $ttl }}</td>
        </tr>
        <tr>
            <td class="pt-label">Pendidikan</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $pendidikan }}</td>
        </tr>
        <tr>
            <td class="pt-label">No HP Aktif</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $phone }}</td>
        </tr>
        <tr>
            <td class="pt-label">Email</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $email }}</td>
        </tr>
    </table>
    <p>Dalam hal ini bertindak untuk dan atas nama diri sendiri ( Pribadi ), yang dalam hal ini selanjutnya disebut
        <strong>PIHAK KEDUA</strong>.</p>

    <p>Bahwa sejalan dengan tugas pokok PT. Damarindo Mandiri yaitu sebagai perusahaan jasa yang bergerak dalam bidang
        penempatan dan pengelolaan sumber daya manusia sesuai dengan job order dari perusahaan atau disebut Outsourcing
        yang sifat pekerjaannya tidak menentu, maka PIHAK PERTAMA dan PIHAK KEDUA telah sepakat untuk mengadakan
        pengikatan Perjanjian Kerja Waktu Tertentu ( Tenaga Kerja Kontrak ), berdasarkan pada :</p>

    <ol class="pasal-list">
        <li>Akte Notaris Udin Narsudin, SH, Nomor 77 Tanggal 31 Oktober 2007 tentang pendirian Perseroan Terbatas, yang
            telah disyahkan oleh Departemen kehakiman dan HAM Nomor: AHU-17509.AH.01 01.Tahun 2008.</li>
        <li>Surat Keputusan Kepala Badan Pelayanan Perijinan Terpadu ( BP2T ) Kabupaten Tangerang Nomor : 568/09 –
            BP2T/2010 Tentang Pemberian Ijin Penyedia Jasa Pekerja / Buruh.</li>
        <li>Nomer Induk Berusaha ( NIB ) Nomer : 9120202811565 Tanggal 16 Agustus 2019.</li>
        <li>Surat Izin Usaha Perdagangan Menengah Nomor : 503.1/0020/30-03/PM/I/2006 dari Dinas Perindustrian dan
            Perdagangan Kabupaten Tangerang tentang penyaluran tenaga kerja, penyelesaian tenaga kerja.</li>
        <li>Surat Izin Usaha Perusahan Penyedia Jasa Pekerja/Buruh Nomor : 9120202811565/78200 Tanggal 16 Agustus 2020.
        </li>
    </ol>

    <p>Maka PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama selanjutnya disebut <strong>KEDUA BELAH PIHAK</strong>
        menerangkan terlebih dahulu :</p>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA adalah perseroan terbatas yang bergerak dalam bidang penempatan dan pengelolaan sumber daya
            manusia yang jenis pekerjaannya didasarkan kepada job order perusahaan pengguna jasa dengan waktu tertentu.
        </li>
        <li>PIHAK PERTAMA sesuai fungsi, wewenang dan tanggung jawab yang diberikan pihak pengguna jasa tenaga kerja,
            adalah perusahaan penyalur tenaga kerja dengan kesepakatan kerja waktu tertentu untuk tenaga alihdaya.</li>
        <li>PIHAK KEDUA adalah tenaga kerja yang memiliki kualifikasi, kemampuan untuk dijadikan tenaga kerja kontrak,
            Pemborongan Pekerjaan pada perusahaan pengguna jasa.</li>
        <li>Perusahaan pengguna jasa adalah perusahaan yang membutuhkan tenaga kerja dari PIHAK PERTAMA untuk
            dipekerjakan pada perusahaan pengguna jasa sesuai dengan keahlian dan order yang diterima oleh PIHAK
            PERTAMA.</li>
    </ol>

    <p>Bahwa dalam rangka upaya memberikan pelayanan kepada perusahaan pengguna tenaga kerja, maka PIHAK PERTAMA dan
        PIHAK KEDUA setuju dan sepakat mengadakan pengikatan yang dituangkan dalam bentuk perjanjian kerja waktu (PKWT)
        untuk Pemborongan Pekerjaan dengan syarat-syarat dan ketentuan sebagaimana diatur dalam pasal-pasal berikut ini
        :</p>

    {{-- ═══════════════════════════════════════════════════════
         PASAL 1
    ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 1<br>Pengertian dan Ketentuan Umum</div>

    <ol class="pasal-list">
        <li>Kesepakatan pengikatan kerja waktu tertentu adalah untuk tenaga alihdaya adalah tenaga kerja yang telah
            mengerti dan setuju untuk ditempatkan oleh PIHAK PERTAMA dengan batasan waktu kerja yang telah ditentukan.
        </li>
        <li>Perjanjian kerja waktu tertentu untuk pemborongan pekerjaan adalah suatu Perjanjian Kerja atau pengikatan
            antara penerima pemborongan pekerjaan dengan tenaga kerja yang waktunya ditentukan oleh perusahaan pemberi
            pekerjaan.</li>
        <li>Jam kerja adalah ketentuan yang harus ditaati dan diikuti oleh pekerja yang ditempatkan PIHAK PERTAMA yang
            jam serta jumlah hari kerjanya terdiri dari 7 ( tujuh ) jam sehari atau 40 ( empat puluh ) jam dalam
            seminggu, atau jam kerja sesuai dengan kebutuhan perusahaan pengguna jasa ( tidak menyimpang dari peraturan
            pemerintah yang berlaku ).</li>
        <li>Pekerjaan lembur adalah pekerjaan yang dilaksanakan diluar jam kerja, dan berpedoman kepada peraturan
            perundang-undangan serta ketentuan yang berlaku.</li>
        <li>Hari Libur Resmi adalah hari libur yang ditetapkan oleh pemerintah.</li>
        <li>Hari Libur lainnya adalah hari libur yang diberikan kepada pekerja selama 1 ( satu ) hari setiap minggu
            setelah melaksanakan pekerjaan selama 6 (enam) hari berturut-turut.</li>
        <li>Upah adalah uang yang diberikan dan diterima oleh tenaga kerja setelah melaksanakan pekerjaan.</li>
        <li>Status PIHAK KEDUA adalah karyawan PIHAK PERTAMA yang ditempatkan di perusahaan pengguna jasa.</li>
    </ol>

    <div class="pasal-heading">Pasal 2<br>Prinsip Perjanjian Alihdaya</div>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA dalam menempatkan PIHAK KEDUA didasarkan kepada penyerahan sebagian pelaksanaan pekerjaan
            kepada perusahaan penerima pemborongan atau perusahaan penyedia jasa pekerja/buruh.</li>
        <li>Penempatan PIHAK KEDUA oleh PIHAK PERTAMA setelah dilakukan pengikatan kesepakatan kerja sama secara tertulis
            antara PIHAK PERTAMA dengan perusahaan yang menggunakan tenaga kerja (perusahaan pengguna jasa).</li>
        <li>PIHAK KEDUA dinyatakan telah mengerti, paham dan setuju untuk ditempatkan oleh PIHAK PERTAMA dan akan selalu
            taat dengan peraturan dari PIHAK PERTAMA.</li>
        <li>PIHAK KEDUA akan mengikuti dan taat kepada peraturan yang ditetapkan oleh PIHAK PERTAMA dan perusahaan
            pengguna jasa tenaga kerja.</li>
    </ol>

    <div class="pasal-heading">Pasal 3<br>Tempat dan Masa Kerja</div>

    <p>PIHAK KEDUA bersedia ditempatkan oleh PIHAK PERTAMA pada :</p>
    <table class="pihak-table">
        <tr>
            <td class="pt-label">1. Perusahaan</td>
            <td class="pt-colon">:</td>
            <td class="pt-value"><strong>{{ $perusahaanPenempatan }}</strong></td>
        </tr>
        <tr>
            <td class="pt-label">2. Beralamat di</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $alamatPenempatan }}</td>
        </tr>
        <tr>
            <td class="pt-label">3. Mulai tanggal</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $mulaiFmt }}</td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════════════
         PASAL 4–5
    ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 4<br>Hak dan Kewajiban Pihak Pertama</div>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA berkewajiban membayar upah setiap bulan 1 (satu) kali, setiap 2 (dua) minggu atau setiap minggu
            1 (satu) kali ataupun dengan ketentuan yang berlaku di perusahaan pemakai jasa kepada PIHAK KEDUA, tanpa
            dapat diwakilkan, kecuali PIHAK KEDUA berhalangan hadir dapat diwakilkan oleh orang lain. Ketentuan
            perwakilan harus sesuai dengan ketentuan hukum, antara lain :
            <ol class="pasal-list alpha">
                <li>Membawa kartu indentitas yang bersangkutan dan identitas yang mewakili.</li>
                <li>Membawa surat kuasa yang dilampiri materai dengan nominal Rp. 10.000,- (sepuluh ribu rupiah) dan
                    fotocopy KTP yang memberi kuasa, di kuasakan 1 lembar dan Kartu Nama (Id Card PT. Damarindo Mandiri )
                    yang bersangkutan.</li>
            </ol>
        </li>
        <li>PIHAK PERTAMA berhak, tanpa sepengetahuan PIHAK KEDUA dapat melakukan pengawasan, menentukan, menambah dan
            mengganti PIHAK KEDUA sewaktu-waktu tanpa pemberitahuan kepada PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA berkewajiban memberi upah / gaji PIHAK KEDUA sesuai dengan hasil kerja dan sistem kerja di
            perusahaan pengguna jasa.</li>
        <li>PIHAK PERTAMA berhak mengatur waktu pembagian upah / gaji PIHAK KEDUA sesuai dengan jadwal yang ditentukan
            PIHAK PERTAMA.</li>
        <li>PIHAK PERTAMA berkewajiban menempatkan kembali PIHAK KEDUA di perusahaan yang sama atau di perusahaan yang
            lain berdasarkan sisa masa kerja di perusahaan sebelumnya.</li>
        <li>PIHAK PERTAMA berhak menentukan tempat kerja PIHAK KEDUA di perusahaan rekanan PIHAK PERTAMA.</li>
    </ol>

    <div class="pasal-heading">Pasal 5<br>Hak dan Kewajiban Pihak Kedua</div>

    <ol class="pasal-list">
        <li>PIHAK KEDUA berkewajiban melaksanakan pekerjaan yang diberikan oleh perusahaan pengguna jasa selama 7
            (tujuh) jam sehari untuk 6 (enam ) hari kerja atau 8 ( delapan ) jam sehari untuk 5 ( lima ) hari kerja atau
            40 ( empat puluh ) jam dalam seminggu.</li>
        <li>PIHAK KEDUA berkewajiban taat serta patuh terhadap waktu kerja dan peraturan di perusahaan pengguna jasa.
        </li>
        <li>PIHAK KEDUA berhak mendapatkan upah lembur, apabila waktu kerja lebih dari sebagaimana dimaksud pada Pasal 5
            ayat 1 diatas, maka dihitung lembur dengan berpedoman pada peraturan pemerintah yang berlaku.</li>
        <li>PIHAK KEDUA wajib berkelakuan baik, sopan santun, tertib dan taat kepada peraturan di perusahaan pengguna
            jasa dan ditempat PIHAK PERTAMA.</li>
        <li>PIHAK KEDUA berkewajiban melaksanakan pekerjaan dengan penuh tanggung jawab.</li>
        <li>PIHAK KEDUA berhak atas hari libur resmi sesuai dengan libur yang ditetapkan oleh pemerintah atau
            perusahaan, diberikan libur selama 1 (satu) hari setelah bekerja selama 1 (satu) minggu berturut-turut.</li>
        <li>PIHAK KEDUA berkewajiban untuk tidak melakukan tindakan provokasi kepada siapapun dan / atau terprovokasi
            dari pihak lain, baik didalam lingkungan kerja maupun diluar lingkungan kerja perusahaan pengguna jasa.</li>
        <li>PIHAK KEDUA bersedia dipindah tugaskan oleh PIHAK PERTAMA ditempat berbeda dimanapun, jika menolak sanksi
            kualifikasi mengundurkan diri.</li>
        <li>PIHAK KEDUA Berhak mendapatkan Cuti bekerja setelah lebih dari 6 ( enam ) bulan masa kerja.</li>
        <li>PIHAK KEDUA Berhak Mendapatkan Tunjangan Hari Raya Sesuai masakerja ( Proret ).</li>
        <li>PIHAK KEDUA berhak memperoleh upah yang besarnya disesuaikan dengan sistem kerja di perusahaan pengguna jasa.
        </li>
        <li>PIHAK KEDUA berkewajiban menjaga nama baik perusahaan pengguna jasa dan PIHAK PERTAMA dimata umum /
            masyarakat.</li>
        <li>PIHAK KEDUA mendapatkan fasiltas kesehatan, apabila ada kartu kesehatan yang di sepakati oleh PIHAK pengguna
            jasa dengan PIHAK PERTAMA apabila terjadi kecelakaan kerja pada saat melaksanakan pekerjaan dan dapat
            berobat ke Poliklinik atau Rumah Sakit yang ditunjuk oleh PIHAK PERTAMA atau pihak perusahaan pengguna jasa.
        </li>
        <li>PIHAK KEDUA yang dipekerjakan mendapatkan fasilitas BPJS Ketenagakerjaan, meliputi Jaminan Kecelakaan Kerja
            (JKK) dan Jaminan Kematian (JKM), serta perlindungan/asuransi sesuai dengan ketentuan dan peraturan
            perundang-undangan yang berlaku selama melaksanakan pekerjaan.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
         PASAL 6–9
    ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 6<br>Denda dan Sanksi</div>

    <ol class="pasal-list">
        <li>KEDUA BELAH PIHAK sepakat apabila PIHAK KEDUA tidak melaksanakan tugas, tanggung jawab dan kewajibannya
            serta tidak melaksanakan pekerjaan dengan baik sesuai isi perjanjian ini dan peraturan yang berlaku, maka
            PIHAK PERTAMA berhak :
            <ol class="pasal-list alpha">
                <li>Memberikan teguran baik secara lisan maupun tulisan kepada PIHAK KEDUA, dan apabila tidak diindahkan,
                    maka akan dilakukan tindakan berupa pemutusan hubungan kerja.</li>
                <li>PIHAK PERTAMA berhak untuk memantau dan mengevaluasi pelaksanaan pekerjaan yang dilaksanakan PIHAK
                    KEDUA.</li>
            </ol>
        </li>
        <li>PIHAK KEDUA harus tunduk dan melaksanakan segala peraturan yang berlaku, tindakan yang menyimpang dinyatakan
            sebagai pelanggaran dan bersedia menerima sanksi apapun dari PIHAK PERTAMA.</li>
        <li>Apabila PIHAK KEDUA melakukan tindakan yang berakibat kerugian dan melawan hukum, maka PIHAK PERTAMA dan /
            atau perusahaan pengguna jasa berhak untuk mengambil langkah-langkah hukum, baik pidana maupun perdata.</li>
    </ol>

    <div class="pasal-heading">Pasal 7<br>Kerahasiaan</div>

    <ol class="pasal-list">
        <li>Semua data dan informasi yang berhubungan dengan isi perjanjian ini bersifat rahasia, dan karenanya akan
            dianggap sebagai dokumen rahasia yang hanya boleh diketahui oleh KEDUA BELAH PIHAK.</li>
        <li>PIHAK KEDUA dalam keadaan dan alasan apapun tidak diperkenankan atau diperbolehkan untuk memberitahukan
            kepada pihak lain / atau membiarkan data atau informasi tersebut menjadi terbuka dan bebas diketahui oleh
            PIHAK KETIGA manapun juga, kecuali atas permintaan dari instansi yang terkait / berwenang dengan persetujuan
            tertulis terlebih dahulu dari PIHAK PERTAMA.</li>
        <li>Pelanggaran atas ketentuan diatas diatur dalam pasal 322 dan 323 Kitab Undang-Undang Hukum Pidana.</li>
    </ol>

    <div class="pasal-heading">Pasal 8<br>Hal-Hal di Luar Kekuasaan / Force Majeur</div>

    <ol class="pasal-list">
        <li>KEDUA BELAH PIHAK sepakat membebaskan masing-masing bila terjadi hal-hal diluar kekuasaan manusia / force
            majeur.</li>
        <li>Yang dimaksud force majeur adalah segala hal yang berkaitan dengan kebakaran, bencana alam, huru-hara,
            peperangan, pemogokan, yang menyeluruh ( massal ) dan adanya peraturan perundangan dan peraturan pemerintah
            atau pengusaha setempat yang secara langsung dapat mempengaruhi kewajiban masing-masing pihak.</li>
    </ol>

    <div class="pasal-heading">Pasal 9<br>Penyelesaian Perselisihan</div>

    <ol class="pasal-list">
        <li>KEDUA BELAH PIHAK sepakat untuk melaksanakan isi kesepakatan / perjanjian berdasarkan itikad baik.</li>
        <li>PIHAK KEDUA tidak akan menuntut dalam bentuk apapun dengan segala sesuatu yang terjadi sebagai dampak dari
            perjanjian ini baik pidana maupun perdata terhadap PIHAK PERTAMA dan perusahaan pengguna jasa.</li>
        <li>Apabila timbul perbedaan penafsiran atau perselisihan diantara KEDUA BELAH PIHAK sehubungan dengan ini dan /
            atau pelaksanaan perjanjian, maka KEDUA BELAH PIHAK sepakat untuk menyelesaikannya secara musyawarah.</li>
        <li>Dalam hal kata mufakat tidak tercapai, maka KEDUA BELAH PIHAK sepakat untuk menyelesaikan perselisihan
            tersebut melalui jalur hukum.</li>
        <li>KEDUA BELAH PIHAK sepakat untuk menunjuk domisili hukum yang tetap dan tak berubah pada Kantor Pengadilan
            Negeri Tangerang.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
         PASAL 10–11 + TANDA TANGAN
    ═══════════════════════════════════════════════════════ --}}
    <div class="page-break-explicit"></div>
    <div class="pasal-heading">Pasal 10<br>Pengakhiran Perjanjian</div>

    <ol class="pasal-list">
        <li>Perjanjian ini akan berakhir dengan sendirinya apabila memenuhi Pasal 3, Pasal 5 ayat 2, ayat 4, ayat 5, ayat
            7, ayat 9, dan pasal 7 dalam perjanjian ini.</li>
        <li>Salah satu pihak menyatakan secara tertulis dengan tegas untuk mengakhiri perjanjian ini.</li>
        <li>PIHAK KEDUA tidak melakukan hal-hal yang dipersyaratkan dalam perjanjian ini ( Wanprestasi ) dan / atau
            melakukan hal-hal yang dilarang dalam perjanjian ini.</li>
        <li>Dalam hal terjadi pengakhiran atau pembatalan perjanjian, maka KEDUA BELAH PIHAK sepakat untuk tidak
            memberlakukan ketentuan yang terdapat dalam pasal 1266 dan 1267 Kitab Undang-Undang Hukum Perdata atau tidak
            ada kewajiban dari PIHAK PERTAMA untuk memberikan uang ganti rugi, pesangon dan / atau uang kebijaksanaan
            dalam bentuk apapun kepada PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA berhak setiap waktu mengakhiri kesepakatan kerja waktu tertentu ini karena adanya alasan
            memaksa atau PIHAK KEDUA tidak masuk kerja tanpa keterangan yang syah ( mangkir ) dan / atau melakukan
            kesalahan / pelanggaran lainnya yang merugikan PIHAK PERTAMA ataupun pihak pengguna jasa, dan / atau dianggap
            tidak cakap / tidak mampu dalam melaksanakan pekerjaan yang dibebankan kepada PIHAK KEDUA.</li>
        <li>Segala sesuatu yang belum diatur dalam perjanjian ini akan diatur dalam perjanjian tersendiri ( addendum /
            Amandemen ) yang merupakan bagian yang menyatu dan tidak terpisahkan dengan perjanjian ini.</li>
    </ol>

    <div class="pasal-heading">Pasal 11<br>Penutup</div>

    <p>Perjanjian ini dibuat dan ditandatangani oleh KEDUA BELAH PIHAK pada hari, tanggal, bulan dan tahun sebagaimana
        tersebut diatas, dibuat dalam kertas bermaterai secukupnya yang sifatnya mengikat serta mempunyai kekuatan hukum
        dan tanpa paksaan / tekanan dari siapapun.</p>

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-label">PIHAK PERTAMA</div>
                <div class="sign-company">PT. DAMARINDO MANDIRI</div>
                <div class="sign-materai">Materai Rp. 10.000,-</div>
                <div class="sign-line">( .............................................. )</div>
                <div class="sign-role">HRD</div>
            </td>
            <td>
                <div class="sign-label">PIHAK KEDUA</div>
                <div class="sign-company">&nbsp;</div>
                <div class="sign-materai">&nbsp;</div>
                <div class="sign-line">( {{ $fullName }} )</div>
                <div class="sign-role">KARYAWAN</div>
            </td>
        </tr>
    </table>

    {{-- Tutup footer paraf di halaman tanda tangan --}}
    <div class="last-page-footer-mask"></div>

    {{-- SURAT PERNYATAAN dipisah ke PDF tersendiri (pdf/surat-pernyataan-outsource.blade.php).
         Section di bawah di-disable sementara — jangan hapus. --}}
    {{--
    <div class="page-break"></div>

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
    --}}

</body>

</html>
