<style>
.hr-sign-img {
  height: 80px;
  display: block;
  margin: 6px 0 8px;
}
</style>
@php
  // Resolve HR signature image as base64 for DomPDF (cannot use URL, must use filesystem path)
  if (!isset($hrSignSrc)) {
      $hrSignPath = public_path('assets/hr-sign.png');
      $hrSignSrc  = null;

      if (file_exists($hrSignPath)) {
          // DomPDF cannot bold an image via CSS, so thicken the strokes with GD (upscale + dilate).
          $hrSignBold = static function (string $path): ?string {
              if (!function_exists('imagecreatefrompng') || !($src = @imagecreatefrompng($path))) {
                  return null;
              }

              $scale  = 3;
              $radius = 1;
              $srcW = imagesx($src);
              $srcH = imagesy($src);
              $w = $srcW * $scale;
              $h = $srcH * $scale;

              $big = imagecreatetruecolor($w, $h);
              imagealphablending($big, false);
              imagesavealpha($big, true);
              imagefilledrectangle($big, 0, 0, $w, $h, imagecolorallocatealpha($big, 0, 0, 0, 127));
              imagecopyresampled($big, $src, 0, 0, 0, 0, $w, $h, $srcW, $srcH);

              $out = imagecreatetruecolor($w, $h);
              imagealphablending($out, false);
              imagesavealpha($out, true);
              imagefilledrectangle($out, 0, 0, $w, $h, imagecolorallocatealpha($out, 0, 0, 0, 127));
              imagealphablending($out, true);
              for ($dy = -$radius; $dy <= $radius; $dy++) {
                  for ($dx = -$radius; $dx <= $radius; $dx++) {
                      if ($dx * $dx + $dy * $dy <= $radius * $radius) {
                          imagecopy($out, $big, $dx, $dy, 0, 0, $w, $h);
                      }
                  }
              }

              ob_start();
              imagepng($out);
              $png = ob_get_clean();
              imagedestroy($src);
              imagedestroy($big);
              imagedestroy($out);

              return $png ? 'data:image/png;base64,' . base64_encode($png) : null;
          };

          $hrSignSrc = $hrSignBold($hrSignPath)
              ?? 'data:image/png;base64,' . base64_encode(file_get_contents($hrSignPath));
      }
  }
@endphp
@if($hrSignSrc)
  <img src="{{ $hrSignSrc }}" class="hr-sign-img" alt="">
@else
  <div class="hr-sign-img" style="background:#f0f0f0; min-height:80px;"></div>
@endif
