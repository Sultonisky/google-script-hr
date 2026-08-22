<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Perjanjian Kerja Waktu Tertentu (PKWT)</title>
  <style>
    @page { margin: 18px 30px; size: A4; }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 9.5px;
      color: #1f2937;
      line-height: 1.45;
      text-align: justify;
    }
    /* Watermark (1:1 GAS) */
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 80pt;
      font-weight: bold;
      color: rgba(200,200,200,0.15);
      z-index: -1;
      pointer-events: none;
    }
    /* Footer paraf (1:1 GAS) */
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      font-size: 7px;
      color: #9ca3af;
      text-align: center;
      border-top: 1px solid #e5e7eb;
      padding: 3px 30px 2px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .pasal-title {
      font-size: 10pt;
      font-weight: bold;
      text-align: center;
      margin: 10px 0 2px;
      text-transform: uppercase;
    }
    .pasal-sep {
      border-top: 0.5px solid #d1d5db;
      margin: 2px 0 4px;
    }
    .pihak-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
    .pihak-table td { padding: 2px 3px; vertical-align: top; }
    .pl { width: 28%; font-weight: bold; }
    .pc { width: 3%; }
    .pv { width: 69%; }
    .sign-table { width: 100%; margin-top: 20px; page-break-inside: avoid; }
    .sign-table td { width: 50%; text-align: center; vertical-align: top; }
    .sign-space { height: 50px; }
    ol, ul { margin: 3px 0 4px; padding-left: 18px; }
    li { margin-bottom: 2px; }
    .indent-1 { margin-left: 8px; }
    .indent-2 { margin-left: 16px; }
    p { margin: 5px 0; }
  </style>
</head>
<body>

  <div class="watermark">CONFIDENTIAL</div>

  @include('pdf.components.kop-surat')

  @php
    // ── Resolve semua variabel (1:1 exportKontrakPKWTPDF GAS) ──────────
    $subject     = $employee ?? $candidate ?? null;
    $fullName    = $subject?->fullName    ?? '-';
    $birthPlace  = $subject?->birthPlace  ?? ($subject?->city ?? '-');
    $rawBirth    = $subject?->birthDate   ?? null;
    $birthFmt    = $rawBirth ? \Carbon\Carbon::parse($rawBirth)->translatedFormat('d F Y') : '-';
    $candBirth   = $birthPlace . ($rawBirth ? ', ' . $birthFmt : '');
    $nik         = ltrim($subject?->nikNpwp ?? $subject?->nik ?? '-', "'");
    $address     = $subject?->residentialAddress ?? $subject?->citizenIdAddress ?? $subject?->address ?? '-';

    $companyName = $company['name']    ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyAddr = $company['address'] ?? '-';
    $companyCity = $company['city']    ?? 'Tangerang';

    $position        = $extraData['position']         ?? $subject?->jobPosition   ?? $subject?->positionApplied ?? 'Staff';
    $department      = $extraData['department']       ?? $subject?->department    ?? 'Operations';
    $division        = $extraData['division']         ?? $subject?->division      ?? '-';
    $directSuperior  = $extraData['direct_superior']  ?? $extraData['directSuperior'] ?? '-';

    $romanMonth  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $contractNo  = $extraData['contract_number'] ?? $extraData['contractNumber']
                    ?? ('PKWT/HRD/' . date('Y') . '/' . ($subject?->recruitmentId ?? '001'));

    // Tanggal dokumen
    $rawDocDate  = $extraData['doc_date'] ?? $extraData['docDate'] ?? null;
    $docDateObj  = $rawDocDate ? \Carbon\Carbon::parse($rawDocDate) : now();
    $dayNames    = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $docDayName  = $dayNames[$docDateObj->dayOfWeek];
    $docDateFmt  = $docDateObj->translatedFormat('d F Y');
    $todayFmt    = now()->translatedFormat('d F Y');

    // Durasi & tanggal kontrak
    $durationStr = $extraData['contract_duration'] ?? $extraData['contractDuration'] ?? '12 Bulan';
    preg_match('/^(\d+)\s*(bulan|tahun)/i', $durationStr, $m);
    $durationNum = (int) ($m[1] ?? 12);
    $durationUnit= strtolower($m[2] ?? 'bulan');
    $durationMonths = $durationUnit === 'tahun' ? $durationNum * 12 : $durationNum;

    // Angka → kata Indonesia
    $onesMap = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan',
                'sepuluh','sebelas','dua belas','tiga belas','empat belas','lima belas',
                'enam belas','tujuh belas','delapan belas','sembilan belas'];
    $tensMap  = ['','sepuluh','dua puluh','tiga puluh','empat puluh','lima puluh'];
    function numWords(int $n): string {
        global $onesMap, $tensMap;
        if ($n < 20) return $onesMap[$n] ?? (string) $n;
        if ($n < 100) { $t = (int)($n/10); $o = $n%10; return $tensMap[$t].($o?" {$onesMap[$o]}":''); }
        if ($n < 200) return 'seratus'.($n%100?" ".numWords($n%100):'');
        if ($n < 1000) { $h=(int)($n/100); return $onesMap[$h]." ratus".($n%100?" ".numWords($n%100):''); }
        return (string)$n;
    }
    $durationWords = numWords($durationMonths);

    $rawJoin = $extraData['join_date'] ?? $extraData['joinDate'] ?? $extraData['contract_start'] ?? null;
    $joinFmt = $rawJoin ? \Carbon\Carbon::parse($rawJoin)->translatedFormat('d F Y') : $docDateFmt;

    $rawEnd  = $extraData['contract_end'] ?? $extraData['contractEnd'] ?? null;
    if (!$rawEnd && $rawJoin) {
        $rawEnd = \Carbon\Carbon::parse($rawJoin)->addMonths($durationMonths)->subDay()->format('Y-m-d');
    }
    $endFmt = $rawEnd ? \Carbon\Carbon::parse($rawEnd)->translatedFormat('d F Y') : '-';

    // Klausul jangka waktu (free text override atau auto-generate)
    $tenorText = $extraData['tenor_text'] ?? $extraData['tenorText']
        ?? "PIHAK PERTAMA dengan ini menyatakan persetujuannya untuk mempekerjakan PIHAK KEDUA sebagai Karyawan PIHAK PERTAMA dengan jangka waktu {$durationNum} ({$durationWords}) bulan terhitung sejak tanggal {$joinFmt} sampai dengan {$endFmt}.";

    // Jam kerja
    $jamMasuk     = $extraData['jam_masuk']     ?? $extraData['jamMasuk']     ?? 'mulai pukul 07.00 WIB dan selambat-lambatnya sampai dengan pukul 07.15 WIB';
    $workSchedule = $extraData['work_schedule'] ?? $extraData['workSchedule'] ?? 'Normal';
    $isShift      = str_contains(strtolower($workSchedule), 'shift');
    $scheduleItem1 = $isShift
        ? '1) Jadwal Waktu Kerja mengikuti pola shift khusus yang disesuaikan dengan kebutuhan operasional Perusahaan, atas kesepakatan antara Pekerja dan Kepala Divisi dari Pekerja yang bersangkutan;'
        : '1) Jadwal Waktu Kerja adalah 5 (lima) hari kerja dalam 1 (satu) minggu, 8 (delapan) jam dalam 1 (satu) hari, dan 40 (empat puluh) jam dalam 1 (satu) minggu;';
  @endphp

  {{-- Footer paraf tiap halaman --}}
  <div class="footer">
    <span></span>
    <span>Paraf &nbsp; <span style="border:1px solid #aaa; display:inline-block; width:30px; height:14px;">&nbsp;</span></span>
  </div>

  {{-- Judul dokumen --}}
  <div style="text-align:center; margin-top:6px">
    <div style="font-size:12pt; font-weight:bold; color:#0b2540; text-transform:uppercase; letter-spacing:0.3px">SURAT PERJANJIAN KERJA WAKTU TERTENTU</div>
    <div style="font-size:10.5pt; font-weight:bold; color:#0b2540; margin-top:-2px">(PKWT)</div>
    <div style="font-size:9pt; color:#4b5563; margin-top:2px">Nomor: {{ $contractNo }}</div>
  </div>

  {{-- Pembuka --}}
  <p>Pada hari ini, <strong>{{ $docDayName }}</strong> tanggal <strong>{{ $docDateFmt }}</strong> kami yang bertandatangan di bawah ini:</p>

  <table class="pihak-table">
    <tr><td class="pl">Nama</td><td class="pc">:</td><td class="pv"><strong>Hisar Hesti</strong></td></tr>
    <tr><td class="pl">Jabatan</td><td class="pc">:</td><td class="pv">HR &amp; Legal Manager</td></tr>
    <tr><td class="pl">Alamat</td><td class="pc">:</td><td class="pv">{{ $companyAddr }}</td></tr>
  </table>
  <p>Dalam hal ini bertindak untuk dan atas nama <strong>{{ $companyName }}</strong> ("Perusahaan") yang selanjutnya disebut sebagai <strong>"PIHAK PERTAMA"</strong>.</p>

  <table class="pihak-table">
    <tr><td class="pl">Nama</td><td class="pc">:</td><td class="pv"><strong>{{ $fullName }}</strong></td></tr>
    <tr><td class="pl">Tempat/Tgl. Lahir</td><td class="pc">:</td><td class="pv">{{ $candBirth }}</td></tr>
    <tr><td class="pl">Nomor KTP</td><td class="pc">:</td><td class="pv">{{ $nik }}</td></tr>
    <tr><td class="pl">Alamat</td><td class="pc">:</td><td class="pv">{{ $address }}</td></tr>
  </table>
  <p>Dalam hal ini bertindak untuk dan atas nama dirinya sendiri yang selanjutnya disebut sebagai <strong>"PIHAK KEDUA"</strong>.</p>

  <p><strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> (secara bersama-sama selanjutnya disebut "PARA PIHAK") terlebih dahulu menerangkan hal-hal sebagai berikut:</p>
  <ol>
    <li>Bahwa PIHAK PERTAMA adalah Perusahaan yang didirikan dan diatur berdasarkan ketentuan hukum yang berlaku di Negara Republik Indonesia, yang membutuhkan tenaga sesuai dengan kebutuhan Perusahaan.</li>
    <li>Bahwa PIHAK KEDUA adalah individu yang telah melamar pekerjaan pada PIHAK PERTAMA dan telah mengikuti proses seleksi/rekrutmen sesuai dengan kualifikasi, kompetensi, pendidikan, pengalaman, dan persyaratan yang ditetapkan oleh PIHAK PERTAMA;</li>
    <li>Bahwa PIHAK PERTAMA bermaksud untuk mempekerjakan PIHAK KEDUA dan PIHAK KEDUA bersedia untuk bekerja pada PIHAK PERTAMA berdasarkan Perjanjian Kerja Waktu Tertentu sebagaimana yang akan diatur dalam Perjanjian ini dengan memperhatikan ketentuan peraturan perundang-undangan yang berlaku di bidang Ketenagakerjaan.</li>
  </ol>
  <p>Berdasarkan hal-hal tersebut di atas, maka PARA PIHAK sepakat dan setuju untuk saling mengikatkan diri dalam Perjanjian Kerja Waktu Tertentu (selanjutnya disebut "Perjanjian") ini dengan ketentuan dan syarat-syarat sebagaimana diuraikan dalam pasal-pasal berikut:</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 1 — Definisi
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 1: DEFINISI</div>
  <div class="pasal-sep"></div>
  <p>Dalam Perjanjian ini yang dimaksud dengan:</p>
  <ol>
    <li>Perjanjian adalah Perjanjian Kerja Waktu Tertentu (PKWT);</li>
    <li>Peraturan Perusahaan adalah Peraturan tertulis yang ditetapkan oleh PIHAK PERTAMA dan berlaku bagi Pekerja serta Perusahaan, yang mengatur mengenai hak, kewajiban, tata tertib, ketentuan kerja, serta aspek hubungan industrial lainnya sesuai dengan ketentuan peraturan perundang-undangan yang berlaku;</li>
    <li>Keputusan Perusahaan adalah setiap keputusan, kebijakan, ketentuan, prosedur, standar operasional, instruksi kerja, atau aturan tertulis lainnya yang ditetapkan oleh PIHAK PERTAMA sebagai pelaksanaan Perjanjian ini dan/atau Peraturan Perusahaan.</li>
    <li>Pemutusan Hubungan Kerja adalah pengakhiran Hubungan Kerja dalam Perjanjian ini karena suatu hal tertentu yang mengakibatkan berakhirnya hak dan kewajiban di antara PARA PIHAK.</li>
    <li>Pengalihan Hubungan Kerja adalah proses pemindahan dan/atau penempatan Pekerja kepada anak Perusahaan dan/atau entitas lain yang memiliki hubungan dengan Perusahaan sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</li>
  </ol>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 2 — Jangka Waktu
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 2: JANGKA WAKTU</div>
  <div class="pasal-sep"></div>
  <p>{{ $tenorText }}</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 3 — Hak Dan Kewajiban Pihak Pertama
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 3: HAK DAN KEWAJIBAN PIHAK PERTAMA</div>
  <div class="pasal-sep"></div>
  <p>1. Hak-hak PIHAK PERTAMA adalah sebagai berikut:</p>
  <div class="indent-1">
    <p>1) PIHAK PERTAMA berhak untuk menerima hasil pekerjaan dari PIHAK KEDUA sesuai dengan tugas dan tanggung jawab yang diberikan;</p>
    <p>2) PIHAK PERTAMA berhak untuk menetapkan kebijakan, peraturan, tata tertib, serta keputusan Perusahaan dalam rangka pelaksanaan hubungan kerja;</p>
    <p>3) PIHAK PERTAMA berhak untuk melakukan penempatan, pemindahan, evaluasi, pembinaan, pemberian peringatan/sanksi, serta tindakan lain sesuai Perjanjian ini, Peraturan Perusahaan dan ketentuan perundang-undangan yang berlaku;</p>
    <p>4) PIHAK PERTAMA berhak untuk memberikan peringatan dan sanksi kepada PIHAK KEDUA dengan ketentuan sebagaimana diatur dalam Peraturan Perusahaan yang berlaku;</p>
    <p>5) PIHAK PERTAMA berhak melakukan Pemutusan Hubungan Kerja dengan PIHAK KEDUA dengan ketentuan sebagaimana diatur dalam Perjanjian dan Peraturan Perusahaan yang berlaku.</p>
  </div>
  <p>2. Kewajiban PIHAK PERTAMA adalah sebagai berikut:</p>
  <div class="indent-1">
    <p>1) PIHAK PERTAMA berkewajiban untuk membayarkan Upah;</p>
    <p>2) PIHAK PERTAMA berkewajiban untuk memberikan Fasilitas Kesejahteraan, termasuk BPJS Kesehatan, BPJS Ketenagakerjaan, Tunjangan Hari Raya Keagamaan, dan hak lainnya sesuai dengan Peraturan Perusahaan;</p>
    <p>3) PIHAK PERTAMA berkewajiban untuk memenuhi seluruh kewajiban Perusahaan sesuai dengan ketentuan Peraturan Perundang-undangan di bidang Ketenagakerjaan.</p>
  </div>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 4 — Hak Dan Kewajiban Pihak Kedua
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 4: HAK DAN KEWAJIBAN PIHAK KEDUA</div>
  <div class="pasal-sep"></div>
  <p>1. Hak-hak PIHAK KEDUA adalah sebagai berikut:</p>
  <div class="indent-1">
    <p>1) PIHAK KEDUA berhak menerima upah dari PIHAK PERTAMA sebagai imbalan atas pekerjaan yang telah dilakukan sesuai dengan jabatan, tugas, tanggung jawab PIHAK KEDUA;</p>
    <p>2) PIHAK KEDUA berhak memperoleh waktu istirahat, waktu libur, waktu cuti, izin serta hak lainnya sesuai Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku;</p>
    <p>3) PIHAK KEDUA berhak untuk memperoleh kesejahteraan, termasuk BPJS Kesehatan, BPJS Ketenagakerjaan, Tunjangan Hari Raya Keagamaan, serta perlindungan keselamatan dan kesehatan kerja sesuai ketentuan yang berlaku.</p>
  </div>
  <p>2. Kewajiban PIHAK KEDUA adalah sebagai berikut:</p>
  <div class="indent-1">
    <p>1) PIHAK KEDUA berkewajiban untuk melaksanakan tugas dan tanggung jawab pekerjaan yang diberikan PIHAK PERTAMA dengan sebaik-baiknya dan dengan penuh tanggung-jawab;</p>
    <p>2) PIHAK KEDUA berkewajiban menaati Peraturan Perusahaan, tata tertib, kebijakan dan ketentuan lain yang berlaku;</p>
    <p>3) PIHAK KEDUA berkewajiban untuk melaksanakan jadwal waktu kerja dengan ketentuan sebagaimana diatur dalam Peraturan Perusahaan dan kebijakan PIHAK PERTAMA;</p>
    <p>4) PIHAK KEDUA berkewajiban untuk menjaga dan memelihara seluruh fasilitas, aset, serta informasi milik PIHAK PERTAMA;</p>
    <p>5) PIHAK KEDUA wajib untuk tidak melakukan kegiatan atau pekerjaan lain yang menimbulkan konflik kepentingan, mengganggu pelaksanaan pekerjaan, menggunakan fasilitas Perusahaan untuk kepentingan pribadi atau merugikan PIHAK PERTAMA;</p>
    <p>6) PIHAK KEDUA tidak melakukan pekerjaan pada pihak lain yang memiliki hubungan usaha sebagai kompetitor PIHAK PERTAMA atau menggunakan informasi dan/atau kekayaan intelektual PIHAK PERTAMA untuk kepentingan pihak lain;</p>
    <p>7) Selama berlangsungnya Perjanjian ini, PIHAK KEDUA tidak sedang atau tidak diperkenankan melakukan hubungan kerja, baik secara langsung maupun tidak langsung, baik pekerjaan tetap/full time maupun pekerjaan sampingan/part time di kompetitor Perusahaan dan/atau Perusahaan lain, termasuk namun tidak terbatas pada pekerjaan sampingan dari pihak ketiga (vendor) yang bekerja sama dengan Perusahaan dan/atau kompetitor Perusahaan;</p>
    <p>8) Apabila Pihak Kedua mengakhiri hubungan kerja sebelum berakhirnya jangka waktu PKWT yang telah disepakati, maka Pihak Kedua akan membayarkan pinalty kepada Pihak Pertama sebesar upah yang diterima atas masa sisa kontrak yang belum terpenuhi.</p>
  </div>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 5 — Ruang Lingkup Pekerjaan
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 5: RUANG LINGKUP PEKERJAAN</div>
  <div class="pasal-sep"></div>
  <p>1. PIHAK PERTAMA menerima dan mempekerjakan PIHAK KEDUA untuk melaksanakan tugas dan tanggung jawab pekerjaan sebagai berikut:</p>
  <div class="indent-1">
    <p>a. Jabatan : {{ $position }}</p>
    <p>b. Divisi/Departemen : {{ $department }}{{ $division && $division !== '-' ? ' / ' . $division : '' }}</p>
    <p>c. Atasan Langsung : {{ $directSuperior }}</p>
  </div>
  <p>2. Dalam melakukan pekerjaannya, PIHAK KEDUA bersedia menerima dan mematuhi arahan-arahan serta instruksi-instruksi dari PIHAK PERTAMA dan/atau atasan langsungnya.</p>
  <p>3. PIHAK KEDUA bersedia ditempatkan dan melaksanakan pekerjaan di lokasi kerja yang ditunjuk oleh PIHAK PERTAMA dan/atau atasan langsungnya.</p>
  <p>4. PIHAK KEDUA bersedia untuk melaksanakan tugas dan tanggung jawab tambahan di luar Ruang Lingkup Pekerjaan yang ditugaskan oleh PIHAK PERTAMA saat diperlukan, sepanjang untuk kepentingan dan kebutuhan Perusahaan dengan memperhatikan hak dan menyesuaikan dengan kemampuan PIHAK KEDUA serta tidak mengurangi hak-hak PIHAK KEDUA berdasarkan ketentuan yang berlaku.</p>
  <p>5. Sehubungan dengan perkembangan kegiatan usaha dan kebutuhan operasional Perusahaan, PIHAK PERTAMA dapat melakukan penyesuaian terhadap jabatan, fungsi, tugas, tanggung jawab, penempatan, mutasi, rotasi, promosi, dan/atau perubahan lainnya terhadap PIHAK KEDUA berdasarkan kebutuhan Perusahaan dengan mempertimbangkan kompetensi, kemampuan, dan ketentuan peraturan perundang-undangan yang berlaku.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 6 — Waktu Kerja
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 6: WAKTU KERJA</div>
  <div class="pasal-sep"></div>
  <p>1. Waktu Kerja adalah waktu yang ditetapkan PIHAK PERTAMA untuk melakukan pekerjaan sesuai dengan tanggung jawab yang diberikan kepada PIHAK KEDUA.</p>
  <p>2. Jadwal Waktu Kerja, Waktu Istirahat Kerja dan Waktu Libur Kerja adalah sebagai berikut:</p>
  <div class="indent-1">
    <p>{{ $scheduleItem1 }}</p>
    <p>2) Waktu Istirahat Kerja adalah sebanyak 1 (satu) jam pada 1 (satu) hari kerja sesuai waktu kerja;</p>
    <p>3) Dalam hal pekerjaan yang dilaksanakan oleh Pekerja memiliki Waktu Kerja berdasarkan shift tertentu, maka Waktu Kerja akan disesuaikan berdasarkan kesepakatan antara Pekerja dan kepala Divisi dari Pekerja yang bersangkutan; dan</p>
    <p>4) Waktu Libur Kerja adalah hari Sabtu dan/atau hari Minggu dan hari libur lainnya yang ditetapkan oleh Pemerintah dan/atau oleh Perusahaan.</p>
  </div>
  <p>3. Ketentuan jam masuk kerja adalah {{ $jamMasuk }}, dengan ketentuan PIHAK KEDUA harus memenuhi jam kerja sebagaimana dimaksud Pasal 6 ayat (2) tersebut di atas.</p>
  <p>4. PIHAK PERTAMA dapat mengubah ketentuan Waktu Kerja tersebut dengan mempertimbangkan keadaan dan kebutuhan Perusahaan.</p>
  <p>5. PIHAK KEDUA bersedia bekerja melebihi Waktu Kerja yang telah ditetapkan apabila diperlukan oleh PIHAK PERTAMA dan dilaksanakan sesuai dengan kebijakan yang ditetapkan dalam Keputusan Perusahaan dan Peraturan Perusahaan.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 7 — Fasilitas Kesejahteraan
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 7: FASILITAS KESEJAHTERAAN</div>
  <div class="pasal-sep"></div>
  <p>1. PIHAK KEDUA berhak memperoleh Fasilitas Kesejahteraan dari PIHAK PERTAMA.</p>
  <p>2. Fasilitas Kesejahteraan sebagaimana dimaksud ayat (1) tersebut di atas terdiri dari:</p>
  <div class="indent-1">
    <p>1) Program Jaminan Sosial Tenaga Kerja (BPJS Ketenagakerjaan/BP Jamsostek);</p>
    <p>2) Program Jaminan Kesehatan (BPJS Kesehatan);</p>
    <p>3) Tunjangan Hari Raya Keagamaan (THRK);</p>
  </div>
  <p>3. Fasilitas Kesejahteraan sebagaimana yang dimaksud Pasal 7 ayat (2) tersebut di atas, dilaksanakan dengan ketentuan sebagaimana diatur dalam Peraturan Perusahaan dan Peraturan perundang-undangan yang berlaku.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 8 — Mangkir
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 8: MANGKIR</div>
  <div class="pasal-sep"></div>
  <p>1. Apabila PIHAK KEDUA tidak hadir bekerja tanpa ada pemberitahuan dan alasan yang sah, maka dianggap sebagai mangkir.</p>
  <p>2. Dalam hal PIHAK KEDUA mangkir selama 5 (lima) hari kerja atau lebih secara berturut-turut tanpa keterangan tertulis dan bukti yang sah, serta telah dilakukan pemanggilan secara patut sebanyak 2 (dua) kali secara tertulis, maka PIHAK KEDUA dapat dianggap mengundurkan diri sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 9 — Pemutusan Hubungan Kerja
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 9: PEMUTUSAN HUBUNGAN KERJA</div>
  <div class="pasal-sep"></div>
  <p>1. PARA PIHAK sepakat untuk mengupayakan agar hubungan kerja tetap berjalan dengan baik. Dalam hal Pemutusan Hubungan Kerja (PHK) tidak dapat dihindarkan, maka pelaksanaannya dilakukan sesuai ketentuan Perjanjian ini, Peraturan Perusahaan, dan peraturan perundang-undangan yang berlaku.</p>
  <p>2. Dalam hal PIHAK KEDUA atas keinginannya sendiri ingin melakukan atau mengajukan Pemutusan Hubungan Kerja kepada PIHAK PERTAMA, maka PIHAK KEDUA akan memberikan pemberitahuan secara tertulis kepada PIHAK PERTAMA yang diajukan sekurang-kurangnya dalam jangka waktu sebelum tanggal Pemutusan Hubungan Kerja tersebut yang disesuaikan dengan pangkat/posisi/level dari PIHAK KEDUA sebagaimana yang diatur dalam Peraturan Perusahaan yakni sebagai berikut:</p>
  <div class="indent-1">
    <p>1) Staff/Specialist/Supervisor/Leader dalam waktu 30 (tiga puluh) hari kalender;</p>
    <p>2) Assistant Manager/Manager I/Manager II/Senior Manager dalam waktu 60 (enam puluh) hari kalender;</p>
    <p>3) General Manager/Director dalam waktu 90 (sembilan puluh) hari kalender.</p>
  </div>
  <p>3. Dalam hal PIHAK KEDUA mengajukan pengunduran diri, maka PIHAK KEDUA wajib menyampaikan pemberitahuan tertulis sesuai ketentuan Peraturan Perusahaan dan peraturan perundang-undangan yang berlaku.</p>
  <p>4. Dalam hal PIHAK PERTAMA melakukan Pemutusan Hubungan Kerja terhadap PIHAK KEDUA yang masih dalam Masa Percobaan, PIHAK PERTAMA akan memberikan pemberitahuan secara tertulis mengenai Pemutusan Hubungan Kerja kepada PIHAK KEDUA dalam waktu selambat-lambatnya 7 (tujuh) hari kerja sebelum tanggal dilakukannya Pemutusan Hubungan Kerja.</p>
  <p>5. Ketentuan lebih lanjut mengenai Pemutusan Hubungan Kerja dilaksanakan sesuai dengan ketentuan sebagaimana diatur dalam Peraturan Perusahaan dan Peraturan perundang-undangan yang berlaku.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 10 — Pengalihan Hubungan Kerja
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 10: PENGALIHAN HUBUNGAN KERJA</div>
  <div class="pasal-sep"></div>
  <p>1. PIHAK PERTAMA dapat melakukan penempatan, pemindahan, atau pengalihan hubungan kerja PIHAK KEDUA kepada perusahaan afiliasi, anak perusahaan, perusahaan induk, atau entitas lain yang memiliki hubungan dengan PIHAK PERTAMA sesuai dengan kebutuhan operasional Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</p>
  <p>2. Dalam hal terjadi Pengalihan Hubungan Kerja, masa kerja dan hak-hak PIHAK KEDUA tetap diperhitungkan sesuai ketentuan yang berlaku dan tidak mengurangi hak normatif PIHAK KEDUA.</p>
  <p>3. Pengalihan Hubungan Kerja tidak menghapus atau mengurangi hak PIHAK KEDUA atas Upah, tunjangan, fasilitas, dan hak lainnya yang telah diperoleh berdasarkan Perjanjian ini, kecuali ditentukan lain sesuai ketentuan peraturan perundang-undangan.</p>
  <p>4. Dalam hal terjadi Pengalihan Hubungan Kerja, hak dan kewajiban PIHAK KEDUA selanjutnya menjadi tanggung jawab pihak penerima pengalihan sesuai dengan kesepakatan pengalihan dan ketentuan hukum yang berlaku.</p>
  <p>5. PARA PIHAK sepakat bahwa Pengalihan Hubungan Kerja dilakukan dengan tetap memperhatikan kepentingan Perusahaan dan perlindungan hak PIHAK KEDUA sesuai ketentuan peraturan perundang-undangan.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 11 — Peringatan Dan Sanksi
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 11: PERINGATAN DAN SANKSI</div>
  <div class="pasal-sep"></div>
  <p>1. PIHAK PERTAMA berhak memberikan Peringatan jika PIHAK KEDUA melakukan pelanggaran dan tidak memenuhi kewajibannya yang telah ditentukan berdasarkan Peraturan Perusahaan dan Peraturan perundang-undangan yang berlaku.</p>
  <p>2. Pemberian peringatan dan/atau sanksi dilakukan sesuai dengan tingkat pelanggaran, ketentuan Peraturan Perusahaan, serta peraturan perundang-undangan yang berlaku.</p>
  <p>3. Ketentuan lebih lanjut mengenai jenis pelanggaran, tata cara pemberian peringatan, dan sanksi diatur dalam Peraturan Perusahaan.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 12 — Force Majeure
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 12: FORCE MAJEURE</div>
  <div class="pasal-sep"></div>
  <p>1. Kegagalan salah satu pihak untuk melaksanakan Perjanjian ini yang disebabkan oleh Force Majeure tidak dianggap sebagai pelanggaran terhadap Perjanjian ini. Yang dimaksud dengan Force Majeure adalah segala keadaan atau peristiwa yang terjadi di luar batas kekuasaan PARA PIHAK, termasuk akan tetapi tidak terbatas pada, huru hara, epidemi, kebakaran, banjir, gempa bumi, pemogokan, perang, keputusan pemerintah yang menghalangi PARA PIHAK secara langsung untuk melaksanakan kewajiban-kewajiban sesuai dengan Perjanjian ini.</p>
  <p>2. Dalam hal terjadinya satu atau beberapa kejadian atau peristiwa Force Majeure, Pihak yang menderita Force Majeure berkewajiban untuk memberitahukan secara tertulis kepada pihak lainnya saat kejadian terjadi.</p>
  <p>3. Jika Force Majeure terjadi selama jangka waktu lebih dari 60 (enam puluh) hari, maka salah satu pihak berhak untuk mengakhiri Perjanjian ini dengan persetujuan dari pihak lainnya.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 13 — Kerahasiaan Informasi
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 13: KERAHASIAAN INFORMASI</div>
  <div class="pasal-sep"></div>
  <p>1. PARA PIHAK sepakat untuk menjaga kerahasiaan atas semua "Informasi Rahasia" yang diterima dan/atau diketahui sehubungan dengan pelaksanaan Perjanjian ini, baik karena jabatan dan pekerjaannya, dan/atau karena sebab lainnya. "Informasi Rahasia" berarti informasi rahasia PARA PIHAK dan klien/pihak lainnya, yang mencakup, namun tidak terbatas pada, pengetahuan, informasi teknis, spesifikasi, sistem, proses, metode, desain, penemuan, rencana, ide, konsep, analisa, strategi pemasaran dan informasi bisnis, kumpulan data, informasi keuangan, informasi mengenai upah karyawan dan perselisihan internal maupun eksternal perusahaan, informasi data karyawan dan informasi lainnya yang dikategorikan sebagai informasi rahasia.</p>
  <p>2. PARA PIHAK sepakat untuk tidak mengungkapkan atau membocorkan atau menyebarluaskan Informasi Rahasia kepada pihak lainnya di luar PARA PIHAK, dan/atau menggunakan informasi tersebut untuk tujuan apapun dan dengan cara apapun, yang dapat merugikan kedua belah pihak tanpa adanya persetujuan tertulis terlebih dahulu.</p>
  <p>3. Ketentuan sebagaimana yang dimaksud pada ayat (1) dalam Pasal ini tidak berlaku apabila pengungkapan tersebut diperlukan menurut hukum atau dengan perintah yang mengikat secara hukum termasuk perintah dari badan peradilan atau pemerintah atau otoritas.</p>
  <p>4. PARA PIHAK sepakat dan wajib memenuhi ketentuan kerahasiaan yang tercantum dalam pasal ini dan apabila terjadi pelanggaran dan/atau kelalaian terhadap ketentuan ini, maka pihak yang melakukan pelanggaran harus bertanggung jawab sepenuhnya atas segala kerugian yang dialami oleh pihak lainnya sesuai dengan kesepakatan PARA PIHAK.</p>
  <p>5. Ketentuan ini tetap berlaku, baik selama berlangsungnya Perjanjian ini maupun setelah berakhirnya Perjanjian ini.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 14 — Larangan, Konflik Kepentingan
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 14: LARANGAN, KONFLIK KEPENTINGAN, DAN PERLINDUNGAN KEPENTINGAN PERUSAHAAN</div>
  <div class="pasal-sep"></div>
  <p>1. PIHAK KEDUA wajib menjaga kepentingan, reputasi, informasi, hubungan bisnis, serta aset milik PIHAK PERTAMA selama berlangsungnya hubungan kerja.</p>
  <p>2. PIHAK KEDUA sepakat bahwa selama terikat hubungan kerja dengan PIHAK PERTAMA dan selama 6 (enam bulan) setelah Pemutusan Hubungan Kerja, PIHAK KEDUA menyatakan dan mengikatkan diri untuk tidak:</p>
  <div class="indent-1">
    <p>1) Melakukan pekerjaan, kegiatan usaha, atau aktivitas lain untuk kepentingan pribadi maupun pihak lain yang memiliki kepentingan yang sama, sejenis, atau bersaing dengan kegiatan usaha PIHAK PERTAMA tanpa persetujuan tertulis dari PIHAK PERTAMA;</p>
    <p>2) Membuat, menjual, menawarkan, memasarkan, membantu, atau memberikan informasi yang berkaitan dengan produk dan/atau jasa yang secara langsung maupun tidak langsung bersaing dengan kegiatan usaha PIHAK PERTAMA;</p>
    <p>3) Menggunakan, memberikan, menyebarluaskan, atau memanfaatkan informasi rahasia, data pelanggan, data mitra, strategi bisnis, metode kerja, dokumen, atau informasi internal PIHAK PERTAMA untuk kepentingan pribadi maupun pihak lain;</p>
    <p>4) Mengajak, membujuk, mempengaruhi, atau mendorong karyawan PIHAK PERTAMA untuk meninggalkan pekerjaannya atau melakukan tindakan yang dapat mengganggu hubungan kerja, produktivitas, dan kepentingan PIHAK PERTAMA;</p>
    <p>5) Mengajak, membujuk, mempengaruhi, atau melakukan tindakan yang menyebabkan pelanggan, distributor, mitra usaha, atau pihak lain yang memiliki hubungan bisnis dengan PIHAK PERTAMA mengurangi, menghentikan, atau mengalihkan hubungan bisnisnya dengan PIHAK PERTAMA.</p>
  </div>
  <p>3. PIHAK KEDUA memahami dan menyetujui bahwa tindakan sebagaimana dimaksud pada ayat (2) merupakan pelanggaran terhadap kewajiban PIHAK KEDUA dalam menjalankan hubungan kerja dengan PIHAK PERTAMA.</p>
  <p>4. Dalam hal PIHAK KEDUA melakukan pelanggaran terhadap ketentuan dalam Pasal ini, PIHAK PERTAMA berhak memberikan tindakan berupa pembinaan, peringatan, dan/atau sanksi sesuai dengan tingkat pelanggaran berdasarkan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</p>
  <p>5. Dalam hal pelanggaran yang dilakukan PIHAK KEDUA mengakibatkan kerugian bagi PIHAK PERTAMA, maka PIHAK PERTAMA berhak melakukan upaya hukum sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 15 — Penyelesaian Perselisihan
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 15: PENYELESAIAN PERSELISIHAN</div>
  <div class="pasal-sep"></div>
  <p>1. PARA PIHAK sepakat bahwa setiap perselisihan yang timbul sehubungan dengan pelaksanaan, penafsiran, dan/atau pengakhiran hubungan kerja berdasarkan Perjanjian ini akan terlebih dahulu diselesaikan secara musyawarah dan kekeluargaan melalui perundingan bipartit.</p>
  <p>2. Apabila penyelesaian melalui perundingan bipartit sebagaimana dimaksud pada ayat (1) tidak mencapai kesepakatan, maka PARA PIHAK sepakat untuk menyelesaikan perselisihan tersebut sesuai dengan mekanisme penyelesaian perselisihan hubungan industrial berdasarkan ketentuan peraturan perundang-undangan yang berlaku.</p>
  <p>3. Apabila perselisihan tersebut tidak dapat diselesaikan secara kekeluargaan maka:</p>
  <div class="indent-1">
    <p>a. Perselisihan Hak, yaitu perselisihan yang timbul karena tidak dipenuhinya hak salah satu pihak akibat adanya perbedaan pelaksanaan atau penafsiran terhadap ketentuan peraturan perundang-undangan, Perjanjian Kerja, Peraturan Perusahaan, atau perjanjian kerja bersama;</p>
    <p>b. Perselisihan Kepentingan, yaitu perselisihan yang timbul dalam hubungan kerja karena adanya ketidaksesuaian pendapat mengenai pembuatan dan/atau perubahan syarat kerja yang ditetapkan dalam hubungan kerja;</p>
    <p>c. Perselisihan Pemutusan Hubungan Kerja, yaitu perselisihan yang timbul karena adanya perbedaan pendapat mengenai pengakhiran hubungan kerja yang dilakukan oleh salah satu pihak.</p>
  </div>
  <p>4. Dalam hal perundingan bipartit tidak mencapai kesepakatan, maka PARA PIHAK dapat menempuh penyelesaian melalui instansi ketenagakerjaan yang berwenang melalui mekanisme mediasi, konsiliasi, atau arbitrase sesuai ketentuan peraturan perundang-undangan.</p>
  <p>5. Apabila penyelesaian melalui mekanisme sebagaimana dimaksud pada ayat (4) tidak menghasilkan kesepakatan, maka PARA PIHAK dapat menempuh upaya hukum melalui Pengadilan Hubungan Industrial sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</p>
  <p>6. PARA PIHAK sepakat bahwa seluruh proses penyelesaian perselisihan akan dilakukan dengan tetap mengedepankan itikad baik, kepatuhan terhadap hukum, dan prinsip hubungan industrial yang harmonis.</p>

  {{-- ═══════════════════════════════════════════════════════
       PASAL 16 — Penutup
  ═══════════════════════════════════════════════════════ --}}
  <div class="pasal-title">PASAL 16: PENUTUP</div>
  <div class="pasal-sep"></div>
  <p>1. Dalam hal PIHAK KEDUA melakukan pelanggaran terhadap ketentuan dalam Perjanjian ini dan/atau Peraturan Perusahaan yang berlaku, maka PIHAK PERTAMA berhak melakukan tindakan sesuai dengan tingkat pelanggaran berdasarkan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</p>
  <p>2. Dalam melaksanakan dan menjalankan Perjanjian ini, PARA PIHAK terikat pada ketentuan dalam Perjanjian ini, Peraturan Perusahaan, kebijakan internal Perusahaan, serta peraturan perundang-undangan yang berlaku di bidang ketenagakerjaan.</p>
  <p>3. Hal-hal yang belum diatur atau belum cukup diatur dalam Perjanjian ini akan mengikuti ketentuan sebagaimana diatur dalam Peraturan Perusahaan dan/atau peraturan perundang-undangan yang berlaku.</p>
  <p>4. Apabila diperlukan perubahan, penambahan, atau penyempurnaan terhadap ketentuan dalam Perjanjian ini, maka PARA PIHAK sepakat untuk menuangkannya dalam bentuk addendum yang merupakan bagian yang tidak terpisahkan dari Perjanjian ini.</p>
  <p>5. Perjanjian ini dibuat dan ditandatangani oleh PARA PIHAK dalam keadaan sadar, tanpa adanya tekanan, paksaan, atau pengaruh dari pihak manapun, serta dilaksanakan dengan itikad baik dan penuh tanggung jawab.</p>

  {{-- ═══════════════════════════════════════════════════════
       Penutup & Tanda Tangan
  ═══════════════════════════════════════════════════════ --}}
  <p style="margin-top:10px">Demikian Perjanjian ini dibuat, dibaca, dipahami, dan ditandatangani oleh PARA PIHAK dalam keadaan sadar, tanpa adanya paksaan, tekanan, maupun pengaruh dari pihak mana pun, serta mempunyai kekuatan hukum yang mengikat bagi PARA PIHAK.</p>

  {{-- Tanda Tangan 2 kolom: Pihak Pertama kiri, Pihak Kedua kanan (1:1 GAS) --}}
  <table class="sign-table" style="margin-top:16px">
    <tr>
      <td style="text-align:left">
        {{ $companyCity }}, {{ $docDateFmt }}<br><br>
        <strong>PIHAK PERTAMA</strong><br>
        <span style="font-size:8px; color:#6b7280">{{ $companyName }}</span><br>
        @include('pdf.components.hr-sign')
        <div style="border-bottom:1px solid #000; width:70%; margin:0 auto"></div>
        <strong><u>Hisar Hesti</u></strong><br>
        <span style="font-size:8px">HR &amp; Legal Manager</span>
      </td>
      <td style="text-align:left; padding-left:20px">
        <br><br>
        <strong>PIHAK KEDUA</strong><br>
        <span style="font-size:8px; color:#6b7280">{{ $fullName }}</span>
        <div class="sign-space"></div>
        <div style="border-bottom:1px solid #000; width:70%; margin:0 auto"></div>
        <strong><u>{{ $fullName }}</u></strong><br>
        <span style="font-size:8px">Karyawan</span>
      </td>
    </tr>
  </table>

</body>
</html>
