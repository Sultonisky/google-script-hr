@php
  // Resolve branch name dari berbagai sumber (1:1 dengan getCompanyProfile() di GAS)
  $branch = $companyEntity
    ?? (isset($employee) ? ($employee->branchName ?? $employee->branch ?? null) : null)
    ?? (isset($candidate) ? ($candidate->branchName ?? null) : null)
    ?? ($extraData['branch_name'] ?? $extraData['company_entity'] ?? null)
    ?? 'PT Mahakarya Sukses Indonesia';

  $bLower = strtolower($branch);

  if (str_contains($bLower, 'stein')) {
    $cName    = 'PT STEIN PERKASA INTERNASIONAL';
    $cAddress = 'Rukan Mangga Dua Square Blok H No. 18-21, Jl. Gunung Sahari Raya Nomor 1, Kel. Ancol, Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta - 14430';
    $cCity    = 'Jakarta';
  } elseif (str_contains($bLower, 'injeksi')) {
    $cName    = 'PT PERKASA INJEKSI INDONESIA';
    $cAddress = 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135';
    $cCity    = 'Tangerang';
  } elseif (str_contains($bLower, 'mitra') || str_contains($bLower, 'elektro')) {
    $cName    = 'PT MITRA ELEKTRO PERKASA';
    $cAddress = 'Rukan Mangga Dua Square Blok H No. 18-21, Jln. Gunung Sahari Raya Nomor 1, Kel. Ancol/Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta';
    $cCity    = 'Jakarta';
  } else {
    // Default: PT Mahakarya Sukses Indonesia
    $cName    = 'PT MAHAKARYA SUKSES INDONESIA';
    $cAddress = 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135';
    $cCity    = 'Tangerang';
  }

  // Make resolved values available to parent view via $company array
  $company = [
    'name'    => $cName,
    'address' => $cAddress,
    'city'    => $cCity,
    'brand'   => explode(' ', $cName)[1] ?? 'MITO',
  ];
@endphp

<!-- KOP SURAT — 1:1 dengan renderKopSuratPDF() di GAS js/pdfExport.html -->
<div style="text-align: center; margin-bottom: 16px; padding-bottom: 2px;">
  {{-- Nama Perusahaan: merah #eb1c24, bold, uppercase (1:1 GAS) --}}
  <div style="font-size: 18pt; font-weight: 800; color: #eb1c24; letter-spacing: 0.5px; text-transform: uppercase; font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.2;">
    {{ $cName }}
  </div>
  {{-- Alamat: hitam normal, 2 baris max --}}
  <div style="font-size: 9pt; color: #1f2937; margin-top: 4px; line-height: 1.5;">
    {{ $cAddress }}
  </div>
  {{-- Double line separator (1:1 GAS) --}}
  <div style="border-top: 2px solid #000000; margin-top: 8px;"></div>
  <div style="border-top: 0.8px solid #000000; margin-top: 1.5px;"></div>
</div>
