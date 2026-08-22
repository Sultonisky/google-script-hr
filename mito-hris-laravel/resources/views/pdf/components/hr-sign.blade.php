@php
  // Resolve HR signature image as base64 for DomPDF (cannot use URL, must use filesystem path)
  if (!isset($hrSignSrc)) {
      $hrSignPath = public_path('assets/hr-sign.png');
      $hrSignSrc  = file_exists($hrSignPath)
          ? 'data:image/png;base64,' . base64_encode(file_get_contents($hrSignPath))
          : null;
  }
@endphp
@if($hrSignSrc)
  <img src="{{ $hrSignSrc }}" style="height:45px; display:block; margin:4px 0 2px;" alt="">
@else
  <div style="height:45px; margin:4px 0 2px;"></div>
@endif
