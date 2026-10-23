<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perjanjian Bersama PHK - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 36px 48px 32px 48px;
            size: A4;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000000;
            line-height: 1.35;
            text-align: justify;
            margin: 0;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 14px;
        }

        p {
            margin: 8px 0;
        }

        .clause {
            margin: 7px 0;
            padding-left: 18px;
            text-indent: -18px;
        }

        .signs {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .signs td {
            width: 50%;
            vertical-align: top;
            text-align: left;
        }

        .sign-space {
            height: 96px;
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
        $hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];

        $formatTanggal = function (string $raw) use ($bulanId): array {
            $raw = trim($raw);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                return [null, '-'];
            }
            try {
                $parsed = \Carbon\Carbon::createFromFormat('Y-m-d', $raw, 'Asia/Jakarta');
            } catch (\Throwable) {
                return [null, '-'];
            }
            if ($parsed === false || $parsed->format('Y-m-d') !== $raw) {
                return [null, '-'];
            }

            return [$parsed, sprintf('%02d %s %d', $parsed->day, $bulanId[$parsed->month - 1], $parsed->year)];
        };

        $terbilang = function (int $nilai) use (&$terbilang): string {
            $angka = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
            if ($nilai < 12) {
                return $angka[$nilai];
            }
            if ($nilai < 20) {
                return $terbilang($nilai - 10) . ' belas';
            }
            if ($nilai < 100) {
                return $terbilang(intdiv($nilai, 10)) . ' puluh' . ($nilai % 10 ? ' ' . $terbilang($nilai % 10) : '');
            }
            if ($nilai < 200) {
                return 'seratus' . ($nilai % 100 ? ' ' . $terbilang($nilai % 100) : '');
            }
            if ($nilai < 1000) {
                return $terbilang(intdiv($nilai, 100)) . ' ratus' . ($nilai % 100 ? ' ' . $terbilang($nilai % 100) : '');
            }
            if ($nilai < 2000) {
                return 'seribu' . ($nilai % 1000 ? ' ' . $terbilang($nilai % 1000) : '');
            }
            if ($nilai < 1000000) {
                return $terbilang(intdiv($nilai, 1000)) . ' ribu' . ($nilai % 1000 ? ' ' . $terbilang($nilai % 1000) : '');
            }
            if ($nilai < 1000000000) {
                return $terbilang(intdiv($nilai, 1000000)) . ' juta' . ($nilai % 1000000 ? ' ' . $terbilang($nilai % 1000000) : '');
            }
            if ($nilai < 1000000000000) {
                return $terbilang(intdiv($nilai, 1000000000)) . ' miliar' . ($nilai % 1000000000 ? ' ' . $terbilang($nilai % 1000000000) : '');
            }

            return (string) $nilai;
        };

        $legalName = trim((string) ($company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA'));
        $companyName = preg_match('/^PT\.?\s+(.+)$/i', $legalName, $companyMatch)
            ? 'PT ' . mb_convert_case(mb_strtolower($companyMatch[1]), MB_CASE_TITLE, 'UTF-8')
            : $legalName;
        $companyAddress = trim((string) ($company['address'] ?? ''));
        if ($companyAddress === '') {
            $companyAddress = '-';
        }
        $companyAddressHtml = str_replace('Jakarta Utara', '<strong>Jakarta Utara</strong>', e($companyAddress));

        $fullName = trim((string) ($employee->fullName ?? ''));
        if ($fullName === '') {
            $fullName = '-';
        }
        $address = trim((string) ($employee->citizenIdAddress ?: $employee->residentialAddress));
        if ($address === '') {
            $address = '-';
        }

        [$agreementObj, $agreementFmt] = $formatTanggal((string) ($extraData['doc_date'] ?? ''));
        [, $lastFmt] = $formatTanggal((string) ($extraData['last_date'] ?? ''));
        $dayName = $agreementObj ? $hariId[$agreementObj->dayOfWeek] : '-';

        $amount = (int) ($extraData['amount'] ?? 0);
        if ($amount > 0) {
            $amountFmt = 'Rp. ' . number_format($amount, 0, ',', '.') . '.00.-';
            $words = $terbilang($amount) . ' rupiah';
            $amountWords = mb_strtoupper(mb_substr($words, 0, 1)) . mb_substr($words, 1);
        } else {
            $amountFmt = '-';
            $amountWords = '-';
        }
    @endphp

    <div class="doc-title">PERJANJIAN BERSAMA</div>

    <p>Pada hari ini <strong>{{ $dayName }}</strong>, tanggal <strong>{{ $agreementFmt }}</strong>, telah terjadi kesepakatan bersama oleh dan antara pihak-pihak yang bertanda tangan di bawah ini :</p>

    <p>I. <strong>Hisar Hesti</strong> selaku Human Resources &amp; Legal Manager bertindak secara sah untuk dan atas nama <strong>{{ $companyName }}</strong> yang beralamat di {!! $companyAddressHtml !!} dan selanjutnya disebut sebagai Pihak I,--------------------------</p>

    <p style="text-align:left">Dan</p>

    <p>II. <strong>{{ $fullName }}</strong> yang beralamat di {{ $address }} dan selanjutnya disebut sebagai Pihak II,--------------------------</p>

    <p>Secara bersama-sama untuk selanjutnya disebut sebagai Para Pihak</p>

    <p>Bahwa Para Pihak telah berunding secara musyawarah mufakat dan menghasilkan Perjanjian sebagai berikut :</p>

    <p class="clause">1. Pihak I dan Pihak II sepakat untuk saling mengakhiri hubungan kerja terhitung sejak tanggal <strong>{{ $lastFmt }}</strong> yang merupakan tanggal terakhir Pihak II bekerja.</p>

    <p class="clause">2. Sehubungan dengan berakhirnya hubungan kerja sebagaimana dimaksud dalam butir (1) diatas, Pihak I memberikan kompensasi dan/atau uang pisah kepada Pihak II sebesar {{ $amountFmt }} ({{ $amountWords }})</p>

    <p class="clause">3. Pihak II dapat menerima dengan baik kondisi berakhirnya hubungan kerja tersebut pada ketentuan angka 1 dan 2 diatas.</p>

    <p class="clause">4. Dengan ditandatanganinya perjanjian ini, maka segala hak dan kewajiban yang ditimbulkan dari hubungan kerja antara kedua belah pihak dinyatakan selesai.</p>

    <p class="clause">5. Pihak II menjamin dan membebaskan Pihak I dari segala hak tuntutan atau gugatan baik yang telah ada dan/atau yang akan timbul di kemudian hari akibat dari pernah diadakannya hubungan kerja dari Pihak I dan Pihak II yang sudah berakhir.</p>

    <p class="clause">6. Pihak II bersepakat tidak akan mempublikasikan maupun melaporkan Pihak I ke pihak manapun terkait dengan berakhirnya hubungan kerja yang di perjanjian dalam perjanjian ini.</p>

    <p class="clause">7. Perjanjian ini mengikat kedua belah pihak dari segala akibat apapun yang ditimbulkannya dan para pihak tidak akan saling mengajukan tuntutan dalam bentuk apapun kelak di kemudian hari.</p>

    <p class="clause">8. Pihak I dan Pihak II membenarkan dan telah menyetujui Perjanjian yang disepakati bersama ini pada tanggal <strong>{{ $agreementFmt }}</strong> yang ditandatangani di dan bersepakat untuk mendaftarkan Perjanjian Bersama ini di Pengadilan Hubungan Industrial pada Pengadilan Negeri <strong>Jakarta Utara</strong>.</p>

    <p>Demikian Perjanjian ini dibuat dan ditandatangani oleh para pihak dalam keadaan sehat jasmani dan rohani tanpa adanya unsur paksaan dari pihak manapun.</p>

    <table class="signs">
        <tr>
            <td>
                <div>Pihak I,</div>
                <div class="sign-space"></div>
                <div class="sign-name">Hisar Hesti</div>
            </td>
            <td>
                <div>Pihak II,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $fullName }}</div>
            </td>
        </tr>
    </table>
</body>

</html>
