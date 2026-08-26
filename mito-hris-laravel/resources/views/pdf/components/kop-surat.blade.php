@php
  // ── 1:1 GAS renderKopSuratPDF() + getCompanyProfile() ──────
  // Resolve branch name dari berbagai sumber (same priority order as GAS getCompanyProfile)
  $branch = (isset($companyEntity) ? $companyEntity : null)
    ?? (isset($employee) ? ($employee->branchName ?? null) : null)
    ?? (isset($candidate) ? ($candidate->branchName ?? null) : null)
    ?? ($extraData['branch_name'] ?? $extraData['company_entity'] ?? null)
    ?? 'PT Mahakarya Sukses Indonesia';

  $bLower = strtolower((string)$branch);

  // 1:1 GAS getCompanyProfile() profile map
  if (str_contains($bLower, 'stein')) {
    $cName    = 'PT STEIN PERKASA INTERNASIONAL';
    $cTagline = 'Steincookware Indonesia - Premium Cookware & Kitchen Appliances';
    $cAddress = 'Rukan Mangga Dua Square Blok H No. 18-21, Jl. Gunung Sahari Raya Nomor 1, Kel. Ancol, Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta - 14430';
    $cCity    = 'Jakarta';
    $cBrand   = 'STEINCOOKWARE';
    $cCode    = 'SPI';
  } elseif (str_contains($bLower, 'injeksi')) {
    $cName    = 'PT PERKASA INJEKSI INDONESIA';
    $cTagline = 'Plastic Injection & Precision Manufacturing Industry';
    $cAddress = 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135';
    $cCity    = 'Tangerang';
    $cBrand   = 'PERKASA INJEKSI';
    $cCode    = 'PII';
  } elseif (str_contains($bLower, 'mitra') || str_contains($bLower, 'elektro')) {
    $cName    = 'PT MITRA ELEKTRO PERKASA';
    $cTagline = 'Electronics Distribution & After Sales Service Network';
    $cAddress = 'Rukan Mangga Dua Square Blok H No. 18-21, Jln. Gunung Sahari Raya Nomor 1, Kel. Ancol/Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta';
    $cCity    = 'Jakarta';
    $cBrand   = 'MITRA ELEKTRO';
    $cCode    = 'MEP';
  } else {
    // Default: PT Mahakarya Sukses Indonesia (1:1 GAS default profile)
    $cName    = 'PT MAHAKARYA SUKSES INDONESIA';
    $cTagline = 'MITO Electronic & Home Appliances';
    $cAddress = 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135';
    $cCity    = 'Tangerang';
    $cBrand   = 'MITO';
    $cCode    = 'MSI';
  }

  // Expose $company array so parent @php blocks can reference it consistently
  // This mirrors GAS: var comp = getCompanyProfile(branchName)
  $company = [
    'name'    => $cName,
    'address' => $cAddress,
    'city'    => $cCity,
    'brand'   => $cBrand,
    'code'    => $cCode,
  ];
@endphp

{{--
  KOP SURAT — 1:1 dengan GAS renderKopSuratPDF()
  Content:
    - Company name: bold, red #eb1c24, centered, uppercase  (1:1 GAS: setFontSize(20), setTextColor(235,28,36))
    - Address: normal, dark, centered, max 2 lines          (1:1 GAS: setFontSize(9.5), splitTextToSize)
    - Double separator line                                  (1:1 GAS: lineWidth 0.7 + 0.2)
  No logo, no contact info, no phone/email — matches GAS exactly.
--}}
<div style="text-align:center; margin-bottom: 4px; padding-bottom: 0;">
  {{-- Company name: 1:1 GAS setFontSize(20), setTextColor(235,28,36), bold --}}
  <div style="font-size: 18pt; font-weight: 800; color: #eb1c24;
              letter-spacing: 0.3px; text-transform: uppercase;
              font-family: 'Times New Roman', Times, serif; line-height: 1.2;">
    {{ $cName }}
  </div>

  {{-- Address: 1:1 GAS setFontSize(9.5), normal, dark, centered --}}
  <div style="font-size: 9pt; color: #1e1e1e; margin-top: 4px; line-height: 1.5;
              font-family: 'Times New Roman', Times, serif;">
    {{ $cAddress }}
  </div>

  {{-- Double line separator: 1:1 GAS lineWidth 0.7 top + 0.2 second --}}
  <div style="border-top: 1.5px solid #000000; margin-top: 7px;"></div>
  <div style="border-top: 0.5px solid #000000; margin-top: 1.5px; margin-bottom: 6px;"></div>
</div>
