<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perjanjian Kerja Waktu Tertentu</title>
    <style>
        /* ── Page setup ──────────────────────────────────────────── */
        @page {
            margin: 26px 46px 42px 46px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.55;
            text-align: justify;
        }

        /* ── Watermark ───────────────────────────────────────────── */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 82pt;
            font-weight: bold;
            color: rgba(74, 74, 74, 0.22);
            z-index: 0;
            pointer-events: none;
            font-family: 'Times New Roman', Times, serif;
            letter-spacing: 6px;
            white-space: nowrap;
        }

        body> :not(.watermark) {
            position: relative;
            z-index: 1;
        }

        /* Paraf tetap tampil pada halaman isi, tetapi ditutup di halaman terakhir. */
        .footer-paraf {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 46px;
            font-size: 10pt;
            text-align: right;
            z-index: 2;
        }

        .paraf-box {
            border: 1px solid #000;
            display: inline-block;
            width: 46px;
            height: 14px;
            margin-left: 4px;
            vertical-align: middle;
        }

        .last-page-footer-mask {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 34px;
            background: #ffffff;
            z-index: 10;
        }

        /* ── Document title ──────────────────────────────────────── */
        .doc-title-wrap {
            text-align: center;
            margin-bottom: 20px;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: none;
        }

        .doc-number {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
        }

        /* ── Body paragraphs ─────────────────────────────────────── */
        p {
            margin: 5px 0;
            text-align: justify;
        }

        /* ── Pihak table (Nama / Jabatan / Alamat) ───────────────── */
        .pihak-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }

        .pihak-table td {
            padding: 1px 2px;
            vertical-align: top;
            font-size: 11pt;
        }

        .pt-label {
            width: 110px;
        }

        .pt-colon {
            width: 12px;
        }

        .pt-value {}

        /* ── Ordered list (pasal content) ───────────────────────── */
        ol.pasal-list {
            margin: 4px 0 4px 0;
            padding-left: 22px;
        }

        ol.pasal-list>li {
            margin-bottom: 3px;
        }

        ol.pasal-list.alpha {
            list-style-type: lower-alpha;
        }

        ol.pasal-list.roman {
            list-style-type: lower-roman;
        }

        /* ── Pasal heading ───────────────────────────────────────── */
        .pasal-heading {
            text-align: center;
            font-weight: bold;
            margin: 14px 0 2px 0;
            font-size: 11pt;
        }

        /* ── Page break ──────────────────────────────────────────── */
        .page-break {
            page-break-before: always;
        }

        /* ── Signature table ─────────────────────────────────────── */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: left;
            padding: 0 8px 0 0;
        }

        .sign-table td:last-child {
            padding: 0 0 0 8px;
        }

        .sign-label {
            font-weight: bold;
            margin-bottom: 12px;
        }

        .sign-line {
            border-bottom: 1px solid #000;
            width: 160px;
            margin-bottom: 2px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .sign-pos {
            font-size: 10.5pt;
        }

        .sign-image-area {
            height: 60px;
            margin: 0 0 2px;
        }

        .sign-image-area .hr-sign-img {
            height: 56px;
            width: auto;
            max-width: 155px;
        }
    </style>
</head>

<body>

    {{-- Watermark CONFIDENTIAL ──────────────────────────────── --}}
    <div class="watermark">CONFIDENTIAL</div>

    {{-- Footer paraf tampil di halaman isi; halaman terakhir ditutup mask. --}}
    <div class="footer-paraf">Paraf: <span class="paraf-box"></span></div>

    @php
        // ── Resolve semua variabel 1:1 template Word ─────────────
        $subject = $employee ?? ($candidate ?? null);
        $fullName = $subject?->fullName ?? '-';
        $birthPlace = $subject?->birthPlace ?? ($subject?->city ?? '-');
        $rawBirth = $subject?->birthDate ?? null;

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
            'Desember',
        ];

        $birthFmt = $rawBirth
            ? (function () use ($rawBirth, $bulanId) {
                try {
                    $d = \Carbon\Carbon::parse($rawBirth);
                    return $d->day . ' ' . $bulanId[$d->month - 1] . ' ' . $d->year;
                } catch (\Throwable $e) {
                    return $rawBirth;
                }
            })()
            : '-';

        $ttl = $birthPlace . ($rawBirth ? ', ' . $birthFmt : '');
        $nik = ltrim($subject?->nikNpwp ?? ($subject?->nik ?? '-'), "'");
        $address = $subject?->residentialAddress ?? ($subject?->citizenIdAddress ?? ($subject?->address ?? '-'));

        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
        $companyAddr = $company['address'] ?? '-';
        $companyCity = $company['city'] ?? 'Jakarta';

        // Nomor kontrak
        $contractNo =
            $extraData['contract_number'] ??
            ($extraData['contractNumber'] ??
                ($extraData['sk_number'] ??
                    ($subject?->contractNumber ?? ($subject?->nomorSk ?? ''))));
        if ($contractNo === '') {
            $contractNo = '-';
        }

        // Tanggal dokumen
        $rawDoc = $extraData['doc_date'] ?? ($extraData['docDate'] ?? null);
        $docObj = $rawDoc ? \Carbon\Carbon::parse($rawDoc) : now();
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $docDay = $dayNames[$docObj->dayOfWeek];
        $docDateFmt = $docObj->day . ' ' . $bulanId[$docObj->month - 1] . ' ' . $docObj->year;
        $docCityDate = $companyCity . ', ' . $docDateFmt;

        // Durasi & tanggal kontrak
        $durationStr = $extraData['contract_duration'] ?? ($extraData['contractDuration'] ?? '12 Bulan');
        preg_match('/^(\d+)\s*(bulan|tahun)/i', $durationStr, $m);
        $durationNum = (int) ($m[1] ?? 12);
        $durationUnit = strtolower($m[2] ?? 'bulan');
        $durationMonths = $durationUnit === 'tahun' ? $durationNum * 12 : $durationNum;

        // Angka → kata Indonesia (untuk klausul jangka waktu)
        $onesMap = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
            'dua belas',
            'tiga belas',
            'empat belas',
            'lima belas',
            'enam belas',
            'tujuh belas',
            'delapan belas',
            'sembilan belas',
        ];
        $tensMap = [
            '',
            'sepuluh',
            'dua puluh',
            'tiga puluh',
            'empat puluh',
            'lima puluh',
            'enam puluh',
            'tujuh puluh',
            'delapan puluh',
            'sembilan puluh',
        ];
        $numWords = function (int $n) use ($onesMap, $tensMap): string {
            if ($n < 20) {
                return $onesMap[$n] ?? (string) $n;
            }
            if ($n < 100) {
                $t = (int) ($n / 10);
                $o = $n % 10;
                return $tensMap[$t] . ($o ? ' ' . $onesMap[$o] : '');
            }
            if ($n < 200) {
                return 'seratus' . ($n % 100 ? ' ' . ($onesMap[$n % 100] ?? '') : '');
            }
            if ($n < 1000) {
                $h = (int) ($n / 100);
                return $onesMap[$h] .
                    ' ratus' .
                    ($n % 100
                        ? ' ' .
                            (function ($x) use ($onesMap, $tensMap) {
                                if ($x < 20) {
                                    return $onesMap[$x];
                                }
                                $t = (int) ($x / 10);
                                $o = $x % 10;
                                return $tensMap[$t] . ($o ? ' ' . $onesMap[$o] : '');
                            })($n % 100)
                        : '');
            }
            return (string) $n;
        };
        $durationWords = $numWords($durationMonths);

        $rawJoin = $extraData['join_date'] ?? ($extraData['joinDate'] ?? ($extraData['contract_start'] ?? null));
        $joinFmt = '-';
        if ($rawJoin) {
            try {
                $j = \Carbon\Carbon::parse($rawJoin);
                $joinFmt = $j->day . ' ' . $bulanId[$j->month - 1] . ' ' . $j->year;
            } catch (\Throwable $e) {
                $joinFmt = $rawJoin;
            }
        }

        $rawEnd = $extraData['contract_end'] ?? ($extraData['contractEnd'] ?? null);
        if (!$rawEnd && $rawJoin) {
            try {
                $rawEnd = \Carbon\Carbon::parse($rawJoin)->addMonths($durationMonths)->subDay()->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        $endFmt = '-';
        if ($rawEnd) {
            try {
                $e = \Carbon\Carbon::parse($rawEnd);
                $endFmt = $e->day . ' ' . $bulanId[$e->month - 1] . ' ' . $e->year;
            } catch (\Throwable $e2) {
                $endFmt = $rawEnd;
            }
        }

        // Klausul jangka waktu (Pasal 2) - Auto-generated, tidak bisa di-custom
        $tenorText = "PIHAK PERTAMA dengan ini menyatakan persetujuannya untuk mempekerjakan PIHAK KEDUA sebagai Karyawan PIHAK PERTAMA dengan jangka waktu {$durationNum} ({$durationWords}) bulan terhitung sejak tanggal {$joinFmt} sampai dengan {$endFmt}.";

        // Pasal 5 — ruang lingkup
        $position = $extraData['position'] ?? ($subject?->jobPosition ?? ($subject?->positionApplied ?? '-'));
        $department = $extraData['department'] ?? ($subject?->department ?? '-');
        $division = $extraData['division'] ?? ($subject?->division ?? '-');
        $directSuperior = $extraData['direct_superior'] ?? ($extraData['directSuperior'] ?? '-');
        $divDeptLabel = $department . ($division && $division !== '-' ? ' / ' . $division : '');

        // Pasal 6 — jam kerja
        $rawWS = $extraData['work_schedule'] ?? ($extraData['workSchedule'] ?? 'Normal');
        $isShift = str_contains(strtolower($rawWS), 'shift');
        $jamMasuk =
            $extraData['jam_masuk'] ??
            ($extraData['jamMasuk'] ?? 'mulai pukul 07.00 WIB dan selambat-lambatnya sampai dengan pukul 07.15 WIB');
        $scheduleAyat = $isShift
            ? 'Jadwal Waktu Kerja mengikuti pola shift khusus yang disesuaikan dengan kebutuhan operasional Perusahaan, atas kesepakatan antara Pekerja dan Kepala Divisi dari Pekerja yang bersangkutan;'
            : 'Jadwal Waktu Kerja adalah 5 (lima) hari kerja dalam 1 (satu) minggu, 8 (delapan) jam dalam 1 (satu) hari, dan 40 (empat puluh) jam dalam 1 (satu) minggu;';
    @endphp

    {{-- ══════════════════════════════════════════════════════
       JUDUL DOKUMEN (1:1 template: bold, underline, centered)
  ══════════════════════════════════════════════════════ --}}
    <div class="doc-title-wrap">
        <div class="doc-title">Perjanjian Kerja Waktu Tertentu</div>
        <div class="doc-number">No. {{ $contractNo }}</div>
    </div>

    {{-- Pembuka ──────────────────────────────────────────────── --}}
    <p>Pada hari ini, <strong>{{ $docDay }}</strong> tanggal <strong>{{ $docDateFmt }}</strong> kami yang
        bertandatangan di bawah ini:</p>

    {{-- PIHAK PERTAMA ─────────────────────────────────────────── --}}
    <table class="pihak-table">
        <tr>
            <td class="pt-label">Nama</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">Hisar Hesti</td>
        </tr>
        <tr>
            <td class="pt-label">Jabatan</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">HR &amp; Legal Manager</td>
        </tr>
        <tr>
            <td class="pt-label">Alamat</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $companyAddr }}</td>
        </tr>
    </table>

    <p>Dalam hal ini bertindak untuk dan atas nama <strong>{{ $companyName }}</strong> ("Perusahaan") yang selanjutnya
        disebut sebagai <strong>"PIHAK PERTAMA"</strong>.</p>

    <br>

    {{-- PIHAK KEDUA ─────────────────────────────────────────────── --}}
    <table class="pihak-table">
        <tr>
            <td class="pt-label">Nama</td>
            <td class="pt-colon">:</td>
            <td class="pt-value"><strong>{{ $fullName }}</strong></td>
        </tr>
        <tr>
            <td class="pt-label">Tempat/tgl. Lahir</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $ttl }}</td>
        </tr>
        <tr>
            <td class="pt-label">Nomor KTP</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $nik }}</td>
        </tr>
        <tr>
            <td class="pt-label">Alamat</td>
            <td class="pt-colon">:</td>
            <td class="pt-value">{{ $address }}</td>
        </tr>
    </table>

    <p>Dalam hal ini bertindak untuk dan atas nama dirinya sendiri yang selanjutnya disebut sebagai <strong>"PIHAK
            KEDUA"</strong>.</p>

    <br>

    {{-- Konsideran ─────────────────────────────────────────────── --}}
    <p><strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> (secara bersama sama selanjutnya disebut
        <strong>"PARA PIHAK"</strong>) terlebih dahulu menerangkan hal-hal sebagai berikut:
    </p>

    <ol class="pasal-list">
        <li>Bahwa PIHAK PERTAMA adalah Perusahaan yang didirikan dan diatur berdasarkan ketentuan hukum yang berlaku di
            Negara Republik Indonesia, yang membutuhkan tenaga sesuai dengan kebutuhan Perusahaan.</li>
        <li>Bahwa PIHAK KEDUA adalah individu yang telah melamar pekerjaan pada PIHAK PERTAMA dan telah mengikuti proses
            seleksi/rekrutmen sesuai dengan kualifikasi, kompetensi, pendidikan, pengalaman, dan persyaratan yang
            ditetapkan oleh PIHAK PERTAMA;</li>
        <li>Bahwa PIHAK PERTAMA bermaksud untuk mempekerjakan PIHAK KEDUA dan PIHAK KEDUA bersedia untuk bekerja pada
            PIHAK PERTAMA berdasarkan Perjanjian Kerja Waktu Tertentu sebagaimana yang akan diatur dalam Perjanjian ini
            dengan memperhatikan ketentuan peraturan perundang-undangan yang berlaku di bidang Ketenagakerjaan.</li>
    </ol>

    <p>Berdasarkan hal-hal tersebut di atas, maka PARA PIHAK sepakat dan setuju untuk saling mengikatkan diri dalam
        Perjanjian Kerja Waktu Tertentu (selanjutnya disebut <strong>"Perjanjian"</strong>) ini dengan ketentuan dan
        syarat-syarat sebagaimana diuraikan dalam pasal-pasal berikut:</p>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 1 — DEFINISI
  ═══════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="pasal-heading">Pasal 1<br>DEFINISI</div>

    <p>Dalam Perjanjian ini yang dimaksud dengan:</p>
    <ol class="pasal-list">
        <li>Perjanjian adalah Perjanjian Kerja Waktu Tertentu (PKWT);</li>
        <li>Peraturan Perusahaan adalah Peraturan tertulis yang ditetapkan oleh PIHAK PERTAMA dan berlaku bagi Pekerja
            serta Perusahaan, yang mengatur mengenai hak, kewajiban, tata tertib, ketentuan kerja, serta aspek hubungan
            industrial lainnya sesuai dengan ketentuan peraturan perundang-undangan yang berlaku</li>
        <li>Keputusan Perusahaan adalah setiap keputusan, kebijakan, ketentuan, prosedur, standar operasional, instruksi
            kerja, atau aturan tertulis lainnya yang ditetapkan oleh PIHAK PERTAMA sebagai pelaksanaan Perjanjian ini
            dan/atau Peraturan Perusahaan.</li>
        <li>Pemutusan Hubungan Kerja adalah pengakhiran Hubungan Kerja dalam Perjanjian ini karena suatu hal tertentu
            yang mengakibatkan berakhirnya hak dan kewajiban di antara PARA PIHAK.</li>
        <li>Pengalihan Hubungan Kerja adalah proses pemindahan dan/atau penempatan Pekerja kepada anak Perusahaan
            dan/atau entitas lain yang memiliki hubungan dengan Perusahaan sesuai dengan ketentuan peraturan
            perundang-undangan yang berlaku</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 2 — JANGKA WAKTU
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 2<br>JANGKA WAKTU</div>

    <p>{{ $tenorText }}</p>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 3 — HAK DAN KEWAJIBAN PIHAK PERTAMA
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 3<br>HAK DAN KEWAJIBAN PIHAK PERTAMA</div>

    <ol class="pasal-list">
        <li>Hak-hak PIHAK PERTAMA adalah sebagai berikut:
            <ol class="pasal-list alpha">
                <li>PIHAK PERTAMA berhak untuk menerima hasil pekerjaan dari PIHAK KEDUA sesuai dengan tugas dan
                    tanggung jawab yang diberikuan;</li>
                <li>PIHAK PERTAMA berhak untuk menetapkan kebijakan, peraturan, tata tertib, serta keputusan Perusahaan
                    dalam rangka pelaksanaan hubungan kerja;</li>
                <li>PIHAK PERTAMA berhak untuk melakukan penempatan, pemindahan, evaluasi, pembinaan, pemberian
                    peringatan/sanksi, serta tindakan lain sesuai Perjanjian ini, Peraturan Perusahaan dan ketentuan
                    perundang-undangan yang berlaku.</li>
                <li>PIHAK PERTAMA berhak untuk memberikan peringatan dan sanksi kepada PIHAK KEDUA dengan ketentuan
                    sebagaimana diatur dalam Peraturan Perusahaan yang berlaku;</li>
                <li>PIHAK PERTAMA berhak melakukan Pemutusan Hubungan Kerja dengan PIHAK KEDUA dengan ketentuan
                    sebagaimana diatur dalam Perjanjian dan Peraturan Perusahaan yang berlaku.</li>
            </ol>
        </li>
        <li>Kewajiban PIHAK PERTAMA adalah sebagai berikut:
            <ol class="pasal-list alpha">
                <li>PIHAK PERTAMA berkewajiban untuk membayarkan Upah.</li>
                <li>PIHAK PERTAMA berkewajiban untuk memberikan Fasilitas Kesejahteraan, termasuk BPJS Kesehatan, BPJS
                    Ketenagakerjaan, Tunjangan Hari Raya Keagamaan, dan hak lainnya sesuai dengan Peraturan Perusahaan.
                </li>
                <li>PIHAK PERTAMA berkewajiban untuk memenuhi seluruh kewajiban Perusahaan sesuai dengan ketentuan
                    Peraturan Perundang-undangan di bidang Ketenagakerjaan.</li>
            </ol>
        </li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 4 — HAK DAN KEWAJIBAN PIHAK KEDUA
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 4<br>HAK DAN KEWAJIBAN PIHAK KEDUA</div>

    <ol class="pasal-list">
        <li>Hak-hak PIHAK KEDUA adalah sebagai berikut:
            <ol class="pasal-list alpha">
                <li>PIHAK KEDUA berhak menerima upah dari PIHAK PERTAMA sebagai imbalan atas pekerjaan yang telah
                    dilakukan sesuai dengan jabatan, tugas, tanggung jawab PIHAK KEDUA.</li>
                <li>PIHAK KEDUA berhak memperoleh waktu istirahat, waktu libur, waktu cuti, izin serta hak lainnya
                    sesuai Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</li>
                <li>PIHAK KEDUA berhak untuk memperoleh kesejahteraan, termasuk BPJS Kesehatan, BPJS Ketenagakerjaan
                    Tunjangan Hari Raya Keagamaan, serta perlindungan keselamatan dan kesehatan kerja sesuai ketentuan
                    yang berlaku.</li>
            </ol>
        </li>
        <li>Kewajiban PIHAK KEDUA adalah sebagai berikut:
            <ol class="pasal-list alpha">
                <li>PIHAK KEDUA berkewajiban untuk melaksanakan tugas dan tanggung jawab pekerjaan yang diberikan PIHAK
                    PERTAMA dengan sebaik-baiknya dan dengan penuh tanggung-jawab.</li>
                <li>PIHAK KEDUA berkewajiban menaati Peraturan Perusahaan, tata tertib, kebijakan dan ketentuan lain
                    yang berlaku.</li>
                <li>PIHAK KEDUA berkewajiban untuk melaksanakan jadwal waktu kerja dengan ketentuan sebagaimana diatur
                    dalam Peraturan Perusahaan dan kebijakan PIHAK PERTAMA.</li>
                <li>PIHAK KEDUA berkewajiban untuk menjaga dan memelihara seluruh fasilitas, aset, serta informasi milik
                    PIHAK PERTAMA.</li>
                <li>PIHAK KEDUA wajib untuk tidak melakukan kegiatan atau pekerjaan lain yang menimbulkan konflik
                    kepentingan, menggganggu pelaksanaan pekerjaan, menggunakan fasilitas Perusahaan untuk kepentingan
                    pribadi atau merugikan PIHAK PERTAMA.</li>
                <li>PIHAK KEDUA tidak melakukan pekerjaan pada pihak lain yang memiliki hubungan usaha sebagai
                    kompetitor PIHAK PERTAMA atau menggunakan informasi dan/atau kekayaan intelektual PIHAK PERTAMA
                    untuk kepentingan pihak lain.</li>
                <li>Selama berlangsungnya Perjanjian ini, PIHAK KEDUA tidak sedang atau tidak diperkenankan melakukan
                    hubungan kerja, baik secara langsung maupun tidak langsung, baik pekerjaan tetap/<em>full time</em>
                    maupun pekerjaan sampingan/<em>part time</em> di kompetitor Perusahaan dan/atau Perusahaan lain,
                    termasuk namun tidak terbatas pada pekerjaan sampingan dari pihak ketiga (vendor) yang bekerja sama
                    dengan Perusahaan dan/atau kompetitor Perusahaan.</li>
                <li>Apabila Pihak Kedua mengakhiri hubungan kerja sebelum berakhirnya jangka waktu PKWT yang telah
                    disepakati, maka Pihak Kedua akan membayarkan pinalty kepada Pihak Pertama sebesar upah yang di
                    terima atas masa sisa kontrak yang belum terpenuhi.</li>
            </ol>
        </li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 5 — RUANG LINGKUP PEKERJAAN
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 5<br>RUANG LINGKUP PEKERJAAN</div>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA menerima dan mempekerjakan PIHAK KEDUA untuk melaksanakan tugas dan tanggung jawab pekerjaan
            sebagai berikut:
            <ol class="pasal-list alpha">
                <li>Jabatan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $position }}</li>
                <li>Divisi &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                    {{ $divDeptLabel }}</li>
                <li>Atasan Langsung : {{ $directSuperior }}</li>
            </ol>
        </li>
        <li>Dalam melakukan pekerjaannya, PIHAK KEDUA bersedia menerima dan mematuhi arahan-arahan serta
            instruksi-instruksi dari PIHAK PERTAMA dan/atau atasan langsungnya.</li>
        <li>PIHAK KEDUA bersedia ditempatkan dan melaksanakan pekerjaan di lokasi kerja yang ditunjuk oleh PIHAK PERTAMA
            dan/atau atasan langsungnya.</li>
        <li>PIHAK KEDUA bersedia untuk melaksanakan tugas dan tanggung jawab tambahan diluar Ruang Lingkup Pekerjaan
            yang ditugaskan oleh PIHAK PERTAMA saat diperlukan, sepanjang untuk kepentingan dan kebutuhan Perusahaan
            dengan memperhatikan hak dan menyesuaikan dengan kemampuan PIHAK KEDUA serta tidak mengurangi hak-hak PIHAK
            KEDUA berdasarkan ketentuan yang berlaku.</li>
        <li>Sehubungan dengan perkembangan kegiatan usaha dan kebutuhan operasional Perusahaan, PIHAK PERTAMA dapat
            melakukan penyesuaian terhadap jabatan, fungsi, tugas, tanggung jawab, penempatan, mutasi, rotasi, promosi,
            dan/atau perubahan lainnya terhadap PIHAK KEDUA berdasarkan kebutuhan Perusahaan dengan mempertimbangkan
            kompetensi, kemampuan, dan ketentuan peraturan perundang-undangan yang berlaku</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 6 — WAKTU KERJA
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 6<br>WAKTU KERJA</div>

    <ol class="pasal-list">
        <li>Waktu Kerja adalah waktu yang ditetapkan PIHAK PERTAMA untuk melakukan pekerjaan sesuai dengan tanggung
            jawab yang diberikan kepada PIHAK KEDUA.</li>
        <li>Jadwal Waktu Kerja, Waktu Istirahat Kerja dan Waktu Libur Kerja adalah sebagai berikut:
            <ol class="pasal-list alpha">
                <li>{{ $scheduleAyat }}</li>
                <li>Waktu Istirahat Kerja adalah sebanyak 1 (satu) jam pada 1 (satu) hari kerja sesuai waktu kerja;</li>
                <li>Dalam hal pekerjaan yang dilaksanakan oleh Pekerja memiliki Waktu Kerja berdasarkan shift tertentu,
                    maka Waktu Kerja akan disesuaikan berdasarkan kesepakatan antara Pekerja dan kepala Divisi dari
                    Pekerja yang bersangkutan; dan</li>
                <li>Waktu Libur Kerja adalah hari Sabtu dan/atau hari Minggu dan hari libur lainnya yang ditetapkan oleh
                    Pemerintah dan/atau oleh Perusahaan.</li>
            </ol>
        </li>
        <li>Ketentuan jam masuk kerja adalah {{ $jamMasuk }}, dengan ketentuan PIHAK KEDUA harus memenuhi jam kerja
            sebagaimana dimaksud Pasal 6 ayat (2) tersebut di atas.</li>
        <li>PIHAK PERTAMA dapat mengubah ketentuan Waktu Kerja tersebut dengan mempertimbangkan keadaan dan kebutuhan
            Perusahaan.</li>
        <li>PIHAK KEDUA bersedia bekerja melebihi Waktu Kerja yang telah ditetapkan apabila diperlukan oleh PIHAK
            PERTAMA dan dilaksanakan sesuai dengan kebijakan yang ditetapkan dalam Keputusan Perusahaan dan Peraturan
            Perusahaan.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 7 — FASILITAS KESEJAHTERAAN
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 7<br>FASILITAS KESEJAHTERAAN</div>

    <ol class="pasal-list">
        <li>PIHAK KEDUA berhak memperoleh Fasilitas Kesejahteraan dari PIHAK PERTAMA.</li>
        <li>Fasilitas Kesejahteraan sebagaimana dimaksud ayat (1) tersebut diatas terdiri dari:
            <ol class="pasal-list alpha">
                <li>Program Jaminan Sosial Tenaga Kerja (BPJS Ketenagakerjaan/BP Jamsostek);</li>
                <li>Program Jaminan Kesehatan (BPJS Kesehatan);</li>
                <li>Tunjangan Hari Raya Keagamaan (THRK);</li>
            </ol>
        </li>
        <li>Fasilitas Kesejahteraan sebagaimana yang dimaksud pasal 7 ayat (2) tersebut di atas, dilaksanakan dengan
            ketentuan sebagaimana diatur dalam Peraturan Perusahaan dan Peraturan perundangan-undangan yang berlaku.
        </li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 8 — MANGKIR
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 8<br>MANGKIR</div>

    <ol class="pasal-list">
        <li>Apabila PIHAK KEDUA tidak hadir bekerja tanpa ada pemberitahuan dan alasan yang sah, maka dianggap sebagai
            mangkir.</li>
        <li>Dalam hal PIHAK KEDUA mangkir selama 5 (lima) hari kerja atau lebih secara berturut-turut tanpa keterangan
            tertulis dan bukti yang sah, serta telah dilakukan pemanggilan secara patut sebanyak 2 (dua) kali secara
            tertulis, maka PIHAK KEDUA dapat dianggap mengundurkan diri sesuai dengan ketentuan peraturan
            perundang-undangan yang berlaku</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 9 — PEMUTUSAN HUBUNGAN KERJA
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 9<br>PEMUTUSAN HUBUNGAN KERJA</div>

    <ol class="pasal-list">
        <li>PARA PIHAK sepakat untuk mengupayakan agar hubungan kerja tetap berjalan dengan baik. Dalam hal Pemutusan
            Hubungan Kerja (PHK) tidak dapat dihindarkan, maka pelaksanaannya dilakukan sesuai ketentuan Perjanjian ini,
            Peraturan Perusahaan, dan peraturan perundang-undangan yang berlaku. PIHAK PERTAMA berhak melakukan PHK dan
            PIHAK KEDUA berhak mengajukan pengunduran diri sesuai dengan prosedur dan ketentuan yang berlaku.</li>
        <li>Dalam hal PIHAK KEDUA atas keinginannya sendiri ingin melakukan atau mengajukan Pemutusan Hubungan Kerja
            kepada PIHAK PERTAMA, maka PIHAK KEDUA akan memberikan pemberitahuan secara tertulis kepada PIHAK PERTAMA
            yang diajukan sekurang-kurangnya dalam jangka waktu sebelum tanggal Pemutusan Hubungan Kerja tersebut yang
            disesuaikan dengan pangkat/posisi/level/ dari PIHAK KEDUA sebagaimana yang diatur dalam Peraturan Perusahaan
            yakni sebagai berikut:
            <ol class="pasal-list alpha">
                <li>Staff/Specialist/Supervisor/Leader dalam waktu 30 (tiga puluh) hari kalender;</li>
                <li>Assistant Manager/Manager I/Manager II/Senior Manager dalam waktu 60 (enam puluh) hari kalender;
                </li>
                <li>General Manager/Director dalam waktu 90 (sembilan puluh) hari kalender.</li>
            </ol>
        </li>
        <li>Dalam hal PIHAK KEDUA mengajukan pengunduran diri, maka PIHAK KEDUA wajib menyampaikan pemberitahuan
            tertulis sesuai ketentuan Peraturan Perusahaan dan peraturan perundang-undangan yang berlaku.</li>
        <li>Dalam hal PIHAK PERTAMA melakukan Pemutusan Hubungan Kerja terhadap PIHAK KEDUA yang masih dalam Masa
            Percobaan, PIHAK PERTAMA akan memberikan pemberitahuan secara tertulis mengenai Pemutusan Hubungan Kerja
            kepada PIHAK KEDUA dalam waktu selambat-lambatnya 7 (tujuh) hari kerja sebelum tanggal dilakukannya
            Pemutusan Hubungan Kerja.</li>
        <li>Ketentuan lebih lanjut mengenai Pemutusan Hubungan Kerja dilaksanakan sesuai dengan ketentuan sebagaimana
            diatur dalam Peraturan Perusahaan dan Peraturan perundangan-undangan yang berlaku.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 10 — PENGALIHAN HUBUNGAN KERJA
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 10<br>PENGALIHAN HUBUNGAN KERJA</div>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA dapat melakukan penempatan, pemindahan, atau pengalihan hubungan kerja PIHAK KEDUA kepada
            perusahaan afiliasi, anak perusahaan, perusahaan induk, atau entitas lain yang memiliki hubungan dengan
            PIHAK PERTAMA sesuai dengan kebutuhan operasional Perusahaan dan ketentuan peraturan perundang-undangan yang
            berlaku</li>
        <li>Dalam hal terjadi Pengalihan Hubungan Kerja, masa kerja dan hak-hak PIHAK KEDUA tetap diperhitungkan sesuai
            ketentuan yang berlaku dan tidak mengurangi hak normatif PIHAK KEDUA</li>
        <li>Pengalihan Hubungan Kerja tidak menghapus atau mengurangi hak PIHAK KEDUA atas Upah, tunjangan, fasilitas,
            dan hak lainnya yang telah diperoleh berdasarkan Perjanjian ini, kecuali ditentukan lain sesuai ketentuan
            peraturan perundang-undangan.</li>
        <li>Dalam hal terjadi Pengalihan Hubungan Kerja, hak dan kewajiban PIHAK KEDUA selanjutnya menjadi tanggung
            jawab pihak penerima pengalihan sesuai dengan kesepakatan pengalihan dan ketentuan hukum yang berlaku.</li>
        <li>PARA PIHAK sepakat bahwa Pengalihan Hubungan Kerja dilakukan dengan tetap memperhatikan kepentingan
            Perusahaan dan perlindungan hak PIHAK KEDUA sesuai ketentuan peraturan perundang-undangan.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 11 — PERINGATAN DAN SANKSI
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 11<br>PERINGATAN DAN SANKSI</div>

    <ol class="pasal-list">
        <li>PIHAK PERTAMA berhak memberikan Peringatan jika PIHAK KEDUA melakukan pelanggaran dan tidak memenuhi
            kewajibannya yang telah ditentukan berdasarkan Peraturan Perusahaan dan Peraturan perundang-undangan yang
            berlaku.</li>
        <li>Pemberian peringatan dan/atau sanksi dilakukan sesuai dengan tingkat pelanggaran, ketentuan Peraturan
            Perusahaan, serta peraturan perundang-undangan yang berlaku.</li>
        <li>Ketentuan lebih lanjut mengenai jenis pelanggaran, tata cara pemberian peringatan, dan sanksi diatur dalam
            Peraturan Perusahaan.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 12 — FORCE MAJEURE
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 12<br>FORCE MAJEURE</div>

    <ol class="pasal-list">
        <li>Kegagalan salah satu pihak untuk melaksanakan Perjanjian ini yang disebabkan oleh Force Majeure tidak
            dianggap sebagai pelanggaran terhadap Perjanjian ini. Yang dimaksud dengan Force Majeure adalah segala
            keadaan atau peristiwa yang terjadi diluar batas kekuasaan PARA PIHAK, termasuk akan tetapi tidak terbatas
            pada, huru hara, epidemi, kebakaran, banjir, gempa bumi, pemogokan, perang, keputusan pemerintah yang
            menghalangi PARA PIHAK secara langsung untuk melaksanakan kewajiban-kewajiban sesuai dengan Perjanjian ini.
        </li>
        <li>Dalam hal terjadinya satu atau beberapa kejadian atau peristiwa Force Majeure, Pihak yang menderita Force
            Majeure berkewajiban untuk memberitahukan secara tertulis kepada pihak lainnya saat kejadian terjadi.</li>
        <li>Jika Force Majeure terjadi selama jangka waktu lebih dari 60 (enam puluh) hari, maka salah satu pihak berhak
            untuk mengakhiri Perjanjian ini dengan persetujuan dari pihak lainnya.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 13 — KERAHASIAAN INFORMASI
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 13<br>KERAHASIAAN INFORMASI</div>

    <ol class="pasal-list">
        <li>PARA PIHAK sepakat untuk menjaga kerahasiaan atas semua "Informasi Rahasia" yang diterima dan/atau diketahui
            sehubungan dengan pelaksanaan Perjanjian ini, baik karena jabatan dan pekerjaannya, dan/atau karena sebab
            lainnya. "Informasi Rahasia" berarti informasi rahasia PARA PIHAK dan klien/pihak lainnya, yang mencakup,
            namun tidak terbatas pada, pengetahuan, informasi teknis, spesifikasi, sistem, proses, metode, desain,
            penemuan, rencana, ide, konsep, analisa, strategi pemasaran dan informasi bisnis, kumpulan data, informasi
            keuangan, informasi mengenai upah karyawan dan perselisihan internal maupun eksternal perusahaan, informasi
            data karyawan dan informasi lainnya yang dikategorikan sebagai informasi rahasia.</li>
        <li>PARA PIHAK sepakat untuk tidak mengungkapkan atau membocorkan atau menyebarluaskan Informasi Rahasia kepada
            pihak lainnya diluar PARA PIHAK, dan/ataupun menggunakan informasi tersebut untuk tujuan apapun dan dengan
            cara apapun, yang dapat merugikan kedua belah pihak tanpa adanya persetujuan tertulis terlebih dahulu.</li>
        <li>Ketentuan sebagaimana yang dimaksud ada ayat (1) dalam Pasal ini tidak berlaku apabila pengungkapan tersebut
            diperlukan menurut hukum atau dengan perintah yang mengikat secara hukum termasuk perintah dari badan
            peradilan atau pemerintah atau otoritas. Dalam hal terdapat perintah yang mengikat secara hukum, PARA PIHAK
            hanya dapat memberikan Informasi Rahasia kepada pihak yang mengeluarkan perintah tersebut dan hanya terbatas
            kepada Informasi Rahasia yang diperlukan oleh pemberi perintah tersebut.</li>
        <li>PARA PIHAK sepakat dan wajib memenuhi ketentuan kerahasiaan yang tercantum dalam pasal ini dan apabila
            terjadi pelanggaran dan/atau kelalaian terhadap ketentuan ini, maka pihak yang melakukan pelanggaran harus
            bertanggung jawab sepenuhnya atas segala kerugian yang dialami oleh pihak lainnya sesuai dengan kesepakatan
            PARA PIHAK.</li>
        <li>Ketentuan ini tetap berlaku, baik selama berlangsungnya Perjanjian ini maupun setelah berakhirnya Perjanjian
            ini.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 14 — LARANGAN & KONFLIK KEPENTINGAN
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 14<br>LARANGAN, KONFLIK KEPENTINGAN, DAN PERLINDUNGAN KEPENTINGAN PERUSAHAAN</div>

    <ol class="pasal-list">
        <li>PIHAK KEDUA wajib menjaga kepentingan, reputasi, informasi, hubungan bisnis, serta aset milik PIHAK PERTAMA
            selama berlangsungnya hubungan kerja.</li>
        <li>PIHAK KEDUA sepakat bahwa selama terikat hubungan kerja dengan PIHAK PERTAMA dan selama 6 (enam bulan)
            setelah Pemutusan Hubungan Kerja, PIHAK KEDUA menyatakan dan mengikatkan diri untuk tidak:
            <ol class="pasal-list alpha">
                <li>Melakukan pekerjaan, kegiatan usaha, atau aktivitas lain untuk kepentingan pribadi maupun pihak lain
                    yang memiliki kepentingan yang sama, sejenis, atau bersaing dengan kegiatan usaha PIHAK PERTAMA
                    tanpa persetujuan tertulis dari PIHAK PERTAMA;</li>
                <li>Membuat, menjual, menawarkan, memasarkan, membantu, atau memberikan informasi yang berkaitan dengan
                    produk dan/atau jasa yang secara langsung maupun tidak langsung bersaing dengan kegiatan usaha PIHAK
                    PERTAMA;</li>
                <li>Menggunakan, memberikan, menyebarluaskan, atau memanfaatkan informasi rahasia, data pelanggan, data
                    mitra, strategi bisnis, metode kerja, dokumen, atau informasi internal PIHAK PERTAMA untuk
                    kepentingan pribadi maupun pihak lain;</li>
                <li>Mengajak, membujuk, mempengaruhi, atau mendorong karyawan PIHAK PERTAMA untuk meninggalkan
                    pekerjaannya atau melakukan tindakan yang dapat mengganggu hubungan kerja, produktivitas, dan
                    kepentingan PIHAK PERTAMA;</li>
                <li>Mengajak, membujuk, mempengaruhi, atau melakukan tindakan yang menyebabkan pelanggan, distributor,
                    mitra usaha, atau pihak lain yang memiliki hubungan bisnis dengan PIHAK PERTAMA mengurangi,
                    menghentikan, atau mengalihkan hubungan bisnisnya dengan PIHAK PERTAMA.</li>
            </ol>
        </li>
        <li>PIHAK KEDUA memahami dan menyetujui bahwa tindakan sebagaimana dimaksud pada ayat (2) merupakan pelanggaran
            terhadap kewajiban PIHAK KEDUA dalam menjalankan hubungan kerja dengan PIHAK PERTAMA.</li>
        <li>Dalam hal PIHAK KEDUA melakukan pelanggaran terhadap ketentuan dalam Pasal ini, PIHAK PERTAMA berhak
            memberikan tindakan berupa pembinaan, peringatan, dan/atau sanksi sesuai dengan tingkat pelanggaran
            berdasarkan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</li>
        <li>Dalam hal pelanggaran yang dilakukan PIHAK KEDUA mengakibatkan kerugian bagi PIHAK PERTAMA, maka PIHAK
            PERTAMA berhak melakukan upaya hukum sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 15 — PENYELESAIAN PERSELISIHAN
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 15<br>PENYELESAIAN PERSELISIHAN</div>

    <ol class="pasal-list">
        <li>PARA PIHAK sepakat bahwa setiap perselisihan yang timbul sehubungan dengan pelaksanaan, penafsiran, dan/atau
            pengakhiran hubungan kerja berdasarkan Perjanjian ini akan terlebih dahulu diselesaikan secara musyawarah
            dan kekeluargaan melalui perundingan bipartit.</li>
        <li>Apabila penyelesaian melalui perundingan bipartit sebagaimana dimaksud pada ayat (1) tidak mencapai
            kesepakatan, maka PARA PIHAK sepakat untuk menyelesaikan perselisihan tersebut sesuai dengan mekanisme
            penyelesaian perselisihan hubungan industrial berdasarkan ketentuan peraturan perundang-undangan yang
            berlaku.</li>
        <li>Apabila perselisihan tersebut tidak dapat diselesaikan secara kekeluargaan maka:
            <ol class="pasal-list alpha">
                <li><strong>Perselisihan Hak</strong>, yaitu perselisihan yang timbul karena tidak dipenuhinya hak salah
                    satu pihak akibat adanya perbedaan pelaksanaan atau penafsiran terhadap ketentuan peraturan
                    perundang-undangan, Perjanjian Kerja, Peraturan Perusahaan, atau perjanjian kerja bersama;</li>
                <li><strong>Perselisihan Kepentingan</strong>, yaitu perselisihan yang timbul dalam hubungan kerja
                    karena adanya ketidaksesuaian pendapat mengenai pembuatan dan/atau perubahan syarat kerja yang
                    ditetapkan dalam hubungan kerja;</li>
                <li><strong>Perselisihan Pemutusan Hubungan Kerja</strong>, yaitu perselisihan yang timbul karena adanya
                    perbedaan pendapat mengenai pengakhiran hubungan kerja yang dilakukan oleh salah satu pihak.</li>
            </ol>
        </li>
        <li>Dalam hal perundingan bipartit tidak mencapai kesepakatan, maka PARA PIHAK dapat menempuh penyelesaian
            melalui instansi ketenagakerjaan yang berwenang melalui mekanisme mediasi, konsiliasi, atau arbitrase sesuai
            ketentuan peraturan perundang-undangan.</li>
        <li>Apabila penyelesaian melalui mekanisme sebagaimana dimaksud pada ayat (4) tidak menghasilkan kesepakatan,
            maka PARA PIHAK dapat menempuh upaya hukum melalui Pengadilan Hubungan Industrial sesuai dengan ketentuan
            peraturan perundang-undangan yang berlaku.</li>
        <li>PARA PIHAK sepakat bahwa seluruh proses penyelesaian perselisihan akan dilakukan dengan tetap mengedepankan
            itikad baik, kepatuhan terhadap hukum, dan prinsip hubungan industrial yang harmonis.</li>
    </ol>

    {{-- ═══════════════════════════════════════════════════════
       PASAL 16 — PENUTUP
  ═══════════════════════════════════════════════════════ --}}
    <div class="pasal-heading">Pasal 16<br>PENUTUP</div>

    <ol class="pasal-list">
        <li>Dalam hal PIHAK KEDUA melakukan pelanggaran terhadap ketentuan dalam Perjanjian ini dan/atau Peraturan
            Perusahaan yang berlaku, maka PIHAK PERTAMA berhak melakukan tindakan sesuai dengan tingkat pelanggaran
            berdasarkan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</li>
        <li>Dalam melaksanakan dan menjalankan Perjanjian ini, PARA PIHAK terikat pada ketentuan dalam Perjanjian ini,
            Peraturan Perusahaan, kebijakan internal Perusahaan, serta peraturan perundang-undangan yang berlaku di
            bidang ketenagakerjaan.</li>
        <li>Hal-hal yang belum diatur atau belum cukup diatur dalam Perjanjian ini akan mengikuti ketentuan sebagaimana
            diatur dalam Peraturan Perusahaan dan/atau peraturan perundang-undangan yang berlaku.</li>
        <li>Apabila diperlukan perubahan, penambahan, atau penyempurnaan terhadap ketentuan dalam Perjanjian ini, maka
            PARA PIHAK sepakat untuk menuangkannya dalam bentuk addendum yang merupakan bagian yang tidak terpisahkan
            dari Perjanjian ini.</li>
        <li>Perjanjian ini dibuat dan ditandatangani oleh PARA PIHAK dalam keadaan sadar, tanpa adanya tekanan, paksaan,
            atau pengaruh dari pihak manapun, serta dilaksanakan dengan itikad baik dan penuh tanggung jawab.</li>
    </ol>

    <p>Demikian Perjanjian ini dibuat, dibaca, dipahami, dan ditandatangani oleh PARA PIHAK dalam keadaan sadar, tanpa
        adanya paksaan, tekanan, maupun pengaruh dari pihak mana pun, serta mempunyai kekuatan hukum yang mengikat bagi
        PARA PIHAK.</p>

    {{-- ═══════════════════════════════════════════════════════
       TANDA TANGAN — Halaman terakhir (1:1 template)
       Kiri: PIHAK PERTAMA | Kanan: PIHAK KEDUA
  ═══════════════════════════════════════════════════════ --}}
    <p style="margin-top:18px;"><strong>{{ $docCityDate }}</strong></p>

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-label">PIHAK PERTAMA</div>
                <div class="sign-image-area">
                    @include('pdf.components.hr-sign')
                </div>
                <div class="sign-line"></div>
                <div class="sign-name">Hisar Hesti</div>
                <div class="sign-pos">HR &amp; Legal Manager</div>
            </td>
            <td>
                <div class="sign-label">PIHAK KEDUA</div>
                <div class="sign-image-area"></div>
                <div class="sign-line"></div>
                <div class="sign-name">{{ $fullName }}</div>
                <div class="sign-pos">Karyawan</div>
            </td>
        </tr>
    </table>

    <div class="last-page-footer-mask"></div>

</body>

</html>
