@php
    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formatDate = static function (?string $value) use ($monthNames): string {
        if (! $value) {
            return '-';
        }

        $date = \Illuminate\Support\Carbon::parse($value);

        return $date->day.' '.$monthNames[$date->month - 1].' '.$date->year;
    };

    $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyAddress = $company['address'] ?? '';
    $companyCity = $company['city'] ?? 'Tangerang';
    $letterNumber = trim((string) ($extraData['sk_number'] ?? ''));
    if (! preg_match('#^\d+/SP/#i', $letterNumber)) {
        $letterNumber = '';
    }

    $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
    $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'") ?: '-';
    $employeePosition = trim((string) ($employee->jobPosition ?? $employee->jobPositionLocation ?? '')) ?: '-';
    $employeeLocation = trim((string) ($employee->lokasiKerja ?? $employee->areaKerja ?? '')) ?: '-';
    $directSuperior = trim((string) ($extraData['superior_name'] ?? ''))
        ?: trim((string) ($employee->directSuperior ?? '')) ?: '-';
    $superiorPosition = trim((string) ($extraData['superior_position'] ?? 'Atasan Langsung')) ?: 'Atasan Langsung';

    $level = \App\Enums\WarningLetterLevel::tryFrom((string) ($extraData['level'] ?? 'SP1'))
        ?? \App\Enums\WarningLetterLevel::SP1;
    $isFinal = $level === \App\Enums\WarningLetterLevel::SP1_FINAL;
    $letterName = match ($level) {
        \App\Enums\WarningLetterLevel::SP1 => 'Surat Peringatan Tertulis ke 1',
        \App\Enums\WarningLetterLevel::SP1_FINAL => 'Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir)',
        \App\Enums\WarningLetterLevel::SP2 => 'Surat Peringatan Tertulis ke 2',
        \App\Enums\WarningLetterLevel::SP3 => 'Surat Peringatan Tertulis ke 3',
    };
    $validMonths = max(1, (int) ($extraData['validity_months'] ?? $level->validityMonths()));
    $numberWords = [1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima', 6 => 'enam'];
    $validityText = $validMonths % 12 === 0
        ? ($validMonths / 12).' ('.($numberWords[$validMonths / 12] ?? $validMonths / 12).') tahun'
        : $validMonths.' ('.($numberWords[$validMonths] ?? $validMonths).') bulan';
    $regulation = trim((string) ($extraData['regulation_reference'] ?? ''))
        ?: trim((string) ($extraData['violation_category'] ?? 'Pelanggaran Peraturan Perusahaan'));
    $violationLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($extraData['violation_description'] ?? ''))))
        ->map(fn ($line) => trim(preg_replace('/^\s*(?:[-*\x{2022}]|\d+[.)])\s*/u', '', $line)))
        ->filter()
        ->values();
    $docDate = (string) ($extraData['doc_date'] ?? '');
    $docDateFormatted = $formatDate($docDate);

    $joinWithDan = static function (array $items): string {
        if (count($items) < 2) {
            return implode('', $items);
        }

        return implode(', ', array_slice($items, 0, -1)).' dan '.end($items);
    };
    $articleLabel = static function (array $ref): string {
        $label = 'Pasal '.trim((string) ($ref['article_number'] ?? ''));
        if (trim((string) ($ref['paragraph_number'] ?? '')) !== '') {
            $label .= ' ayat ('.trim((string) $ref['paragraph_number']).')';
        }
        if (trim((string) ($ref['article_letter'] ?? '')) !== '') {
            $label .= ' huruf '.strtolower(trim((string) $ref['article_letter']));
        }

        return $label;
    };
    $violations = collect($isFinal && is_array($extraData['violations'] ?? null) ? $extraData['violations'] : [])
        ->filter(fn ($row) => is_array($row) && trim((string) ($row['description'] ?? '')) !== '')
        ->map(function (array $row) use ($articleLabel, $joinWithDan) {
            $references = collect(is_array($row['references'] ?? null) ? $row['references'] : [])
                ->filter(fn ($ref) => is_array($ref) && trim((string) ($ref['article_number'] ?? '')) !== '')
                ->map(fn (array $ref) => [
                    'label' => $articleLabel($ref),
                    'type' => trim((string) ($ref['regulation_type'] ?? '')),
                    'text' => trim((string) ($ref['article_text'] ?? '')),
                ])
                ->values();
            $types = $references->pluck('type')->unique();
            $citation = $types->count() === 1
                ? $joinWithDan($references->map(fn ($ref) => '<strong>'.e($ref['label']).'</strong>')->all())
                    .($types->first() !== '' ? ' '.e($types->first()) : '')
                : $joinWithDan($references->map(fn ($ref) => '<strong>'.e($ref['label']).'</strong>'
                    .($ref['type'] !== '' ? ' '.e($ref['type']) : ''))->all());

            return [
                'description' => rtrim(trim((string) $row['description']), " \t.,;:"),
                'citation' => $citation,
                'references' => $references,
                'quoted' => $references->filter(fn ($ref) => $ref['text'] !== '')->values(),
            ];
        })
        ->values();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan Tertulis - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 38px 54px 42px;
        }

        body {
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.28;
        }

        .letterhead {
            margin: 0 0 12px;
            padding: 0 0 8px;
            border-bottom: 1.5px solid #d87943;
            text-align: center;
        }

        .company-name {
            color: #eb1c24;
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .company-address {
            margin-top: 2px;
            font-size: 8pt;
            line-height: 1.35;
        }

        .title-block {
            margin-bottom: 15px;
            text-align: center;
        }

        .title {
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
        }

        .number {
            font-size: 9pt;
            font-weight: bold;
        }

        .company-intro {
            margin: 0 0 8px;
            font-weight: bold;
        }

        .intro {
            margin: 0 0 2px;
        }

        .employee-table {
            margin: 0 0 10px 36px;
            border-collapse: collapse;
        }

        .employee-table td {
            padding: 0;
            vertical-align: top;
        }

        .employee-label {
            width: 76px;
        }

        .employee-colon {
            width: 10px;
        }

        p {
            margin: 0 0 8px;
            text-align: justify;
        }

        .violation-list {
            margin: 0 0 10px 20px;
            padding-left: 14px;
        }

        .violation-list li {
            margin-bottom: 3px;
            padding-left: 2px;
            text-align: justify;
        }

        .violation-list li.violation-item {
            margin-bottom: 10px;
        }

        .article-heading {
            margin-top: 8px;
            font-weight: bold;
        }

        .article-quote {
            margin-top: 2px;
            font-weight: bold;
            text-align: justify;
        }

        .signatures {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .signatures .employee-sign,
        .signatures .company-sign {
            text-align: center;
        }

        .signature-date {
            padding-bottom: 8px !important;
            text-align: center;
        }

        .sign-space {
            height: 72px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .acknowledgement {
            margin-top: 12px;
            text-align: center;
            page-break-inside: avoid;
        }

        .acknowledgement-space {
            height: 72px;
        }

        .signatures td.sign-title {
            vertical-align: middle;
        }

        .draft-watermark {
            position: fixed;
            top: 38%;
            left: 0;
            right: 0;
            color: rgba(235, 28, 36, 0.16);
            font-size: 96pt;
            font-weight: bold;
            letter-spacing: 12px;
            text-align: center;
            transform: rotate(-35deg);
        }

        @if ($isFinal)
            @page {
                margin: 112px 54px 64px;
            }

            .letterhead {
                position: fixed;
                top: -90px;
                left: 0;
                right: 0;
                margin: 0;
            }

            .signatures .signature-date {
                text-align: left;
            }
        @endif

        @unless ($isFinal)
            body {
                font-size: 11pt;
                line-height: 1.3;
            }

            .title {
                font-size: 12pt;
            }

            .number {
                font-size: 11pt;
            }

            .employee-label {
                width: 92px;
            }
        @endunless
    </style>
</head>

<body>
    @if (! empty($extraData['draft']))
        <div class="draft-watermark">DRAFT</div>
    @endif
    <div class="letterhead">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-address">{{ $companyAddress }}</div>
    </div>

    <div class="title-block">
        <div class="title">SURAT PERINGATAN TERTULIS</div>
        @if ($letterNumber !== '')
            <div class="number">Nomor: {{ $letterNumber }}</div>
        @endif
    </div>

    @if ($isFinal)
        <p class="intro">Dengan ini diberikan <strong>{{ $letterName }}</strong> kepada:</p>
    @else
        <div class="company-intro">{{ $companyName }}</div>
        <p class="intro">Dengan ini diberikan {{ $letterName }} Kepada:</p>
    @endif

    <table class="employee-table">
        <tr>
            <td class="employee-label">Nama</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeName }}</td>
        </tr>
        <tr>
            <td class="employee-label">NIK</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeNik }}</td>
        </tr>
        <tr>
            <td class="employee-label">Jabatan</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeePosition }}</td>
        </tr>
        <tr>
            <td class="employee-label">Lokasi</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeLocation }}</td>
        </tr>
    </table>

    <p>
        Karena telah melakukan tindakan pelanggaran terhadap Tata Tertib dan Peraturan Perusahaan{{ $isFinal ? ',' : '' }} berupa:
    </p>
    <ol class="violation-list">
        @if ($violations->isNotEmpty())
            @foreach ($violations as $violation)
                <li class="violation-item">
                    @if ($violation['references']->isEmpty())
                        {{ $violation['description'] }}.
                    @else
                        {{ $violation['description'] }}, sebagaimana diatur dalam {!! $violation['citation'] !!}{{ $violation['quoted']->isNotEmpty() ? ', yang menyatakan:' : '.' }}
                    @endif
                    @if ($violation['references']->count() === 1 && $violation['quoted']->isNotEmpty())
                        <div class="article-quote">"{!! nl2br(e($violation['quoted']->first()['text'])) !!}"</div>
                    @else
                        @foreach ($violation['quoted'] as $quoted)
                            <div class="article-heading">{{ $quoted['label'] }}:</div>
                            <div class="article-quote">"{!! nl2br(e($quoted['text'])) !!}"</div>
                        @endforeach
                    @endif
                </li>
            @endforeach
        @else
            @forelse ($violationLines as $violationLine)
                <li>
                    @if ($regulation !== '')
                        <strong>{{ $regulation }}:</strong>
                    @endif
                    {{ $violationLine }}
                    @if ($loop->first && ! empty($extraData['incident_date']))
                        (Tanggal kejadian: {{ $formatDate($extraData['incident_date']) }})
                    @endif
                </li>
            @empty
                <li>{{ $regulation }}</li>
            @endforelse
        @endif
    </ol>

    @if ($isFinal)
        <p>
            Berdasarkan pelanggaran-pelanggaran tersebut, Perusahaan memberikan
            <strong>Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir)</strong> kepada Saudara sebagai
            bentuk pembinaan dan penegakan disiplin kerja.
        </p>
        <p>
            <strong>Surat Peringatan Tertulis Pertama dan Terakhir</strong> ini berlaku selama
            <strong>1 (satu) tahun</strong> sesuai dengan ketentuan
            <strong>Pasal 47 ayat (1) Peraturan Perusahaan</strong>.
        </p>
        <p>
            Selama masa berlaku Surat Peringatan ini, Saudara wajib memperbaiki kedisiplinan, mematuhi waktu kerja yang
            telah ditentukan, melaksanakan seluruh tugas dan tanggung jawab sesuai dengan ketentuan yang berlaku, serta
            menaati setiap perintah dan/atau instruksi yang diberikan oleh Atasan maupun Pimpinan Perusahaan.
        </p>
        <p>
            Apabila Saudara kembali melakukan pelanggaran terhadap Tata Tertib dan/atau Peraturan Perusahaan selama masa
            berlaku <strong>Surat Peringatan Pertama dan Terakhir</strong> ini, maka Perusahaan dapat mengambil tindakan lebih lanjut
            termasuk <strong>Pemutusan Hubungan Kerja (PHK)</strong> sesuai dengan ketentuan Peraturan Perusahaan dan perundang-undangan
            ketenagakerjaan yang berlaku.
        </p>
        <p>
            Demikian Surat Peringatan Tertulis Pertama dan Terakhir ini diberikan untuk menjadi perhatian dan
            dilaksanakan dengan penuh tanggung jawab.
        </p>
    @else
        <p>
            {{ $letterName }} ini{{ $level === \App\Enums\WarningLetterLevel::SP3 ? ' merupakan peringatan terakhir dan' : '' }}
            berlaku {{ $validityText }}, apabila setelah mendapat Surat
            Peringatan Tertulis ini Saudara melakukan kembali tindakan pelanggaran disiplin maupun pelanggaran di dalam
            Peraturan Perusahaan, maka Perusahaan dapat
            @switch($level)
                @case(\App\Enums\WarningLetterLevel::SP2)
                    memberikan <strong>Surat Peringatan Tertulis ke 3</strong>
                    @break
                @case(\App\Enums\WarningLetterLevel::SP3)
                    melakukan <strong>Pemutusan Hubungan Kerja (PHK)</strong>
                    @break
                @default
                    memberikan sanksi
            @endswitch
            sesuai dengan Peraturan Perusahaan / Undang-Undang Ketenagakerjaan yang berlaku.
        </p>
        <p>
            Demikian Surat Peringatan Tertulis ini diberikan, dan agar dapatnya Saudara memperbaiki tindakan pelanggaran
            tersebut.
        </p>
    @endif

    <table class="signatures">
        <tr>
            <td colspan="2" class="signature-date">{{ $companyCity }}, {{ $docDateFormatted }}</td>
        </tr>
        <tr>
            <td class="employee-sign sign-title">Yang Bersangkutan</td>
            <td class="company-sign sign-title">
                @unless ($isFinal)
                    {{ $companyName }}<br>
                @endunless
                Atasan
            </td>
        </tr>
        <tr>
            <td class="employee-sign">
                <div class="sign-space"></div>
                <span class="sign-name">{{ $employeeName }}</span><br>
                {{ $employeePosition }}
            </td>
            <td class="company-sign">
                <div class="sign-space"></div>
                <span class="sign-name">{{ $directSuperior }}</span><br>
                {{ $superiorPosition }}
            </td>
        </tr>
    </table>

    <div class="acknowledgement">
        Mengetahui
        <div class="acknowledgement-space"></div>
        <span class="sign-name">Hisar Hesti</span><br>
        {{ $isFinal ? 'Human Resources Manager' : 'Human Resources & Legal Manager' }}
    </div>
</body>

</html>
