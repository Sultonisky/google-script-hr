<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Prepares uploaded attachment photos (rekap absensi, resi pengiriman, dll.)
 * to be embedded as pages of a generated letter PDF.
 */
class LetterAttachmentImageService
{
    private const MAX_EDGE_PX = 1600;
    private const JPEG_QUALITY = 82;

    /**
     * @return array{src:string, width:int, height:int}
     *
     * @throws RuntimeException when the file is not a readable image
     */
    public function prepare(UploadedFile $file): array
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($bytes);
        if ($bytes === '' || $info === false) {
            throw new RuntimeException('File lampiran bukan gambar yang valid.');
        }

        if (! function_exists('imagecreatefromstring')) {
            return [
                'src' => 'data:'.$info['mime'].';base64,'.base64_encode($bytes),
                'width' => (int) $info[0],
                'height' => (int) $info[1],
            ];
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('File lampiran bukan gambar yang valid.');
        }

        $image = $this->applyExifOrientation($image, $file, (int) $info[2]);
        $image = $this->downscale($image);

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();

        return [
            'src' => 'data:image/jpeg;base64,'.base64_encode($jpeg),
            'width' => imagesx($canvas),
            'height' => imagesy($canvas),
        ];
    }

    private function applyExifOrientation(\GdImage $image, UploadedFile $file, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) ((@exif_read_data($file->getRealPath()) ?: [])['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        return $rotated instanceof \GdImage ? $rotated : $image;
    }

    private function downscale(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);
        if ($longest <= self::MAX_EDGE_PX) {
            return $image;
        }

        $ratio = self::MAX_EDGE_PX / $longest;
        $scaled = imagescale($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));

        return $scaled instanceof \GdImage ? $scaled : $image;
    }
}
