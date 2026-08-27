<style>
.hr-sign-img {
  height: 60px;
  display: block;
  margin: 6px 0 8px;
}
</style>
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
  <img src="{{ $hrSignSrc }}" class="hr-sign-img" alt="">
@else
  <div class="hr-sign-img" style="background:#f0f0f0; min-height:60px;"></div>
@endif
