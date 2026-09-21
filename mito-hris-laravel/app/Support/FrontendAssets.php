<?php

namespace App\Support;

class FrontendAssets
{
    /**
     * @param  list<string>  $entries  Vite input keys such as resources/js/app.js
     */
    public static function ready(array $entries): bool
    {
        $manifestPath = public_path('build/manifest.json');
        if (!is_file($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            return false;
        }

        foreach ($entries as $entry) {
            $file = is_array($manifest[$entry] ?? null) ? ($manifest[$entry]['file'] ?? null) : null;
            if (!is_string($file) || $file === '' || !is_file(public_path('build/' . ltrim($file, '/')))) {
                return false;
            }
        }

        return true;
    }
}
