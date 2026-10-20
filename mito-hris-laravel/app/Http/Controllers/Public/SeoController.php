<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Response;

class SeoController
{
    public function robots(): Response
    {
        $path = public_path('robots.txt');
        $content = is_file($path)
            ? (string) file_get_contents($path)
            : "User-agent: *\nAllow: /\n";

        if (!str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . "</urlset>\n";

        return response($content, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
