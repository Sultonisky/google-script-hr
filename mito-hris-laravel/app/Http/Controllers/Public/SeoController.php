<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Response;

class SeoController
{
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /hr/',
            'Disallow: /login',
            'Disallow: /logout',
            'Disallow: /apply',
            'Disallow: /career/submission-success',
            'Disallow: /success',
            'Disallow: /outsource/success',
            'Disallow: /check-status',
            'Disallow: /self-update/',
            'Disallow: /outsource/apply',
            'Sitemap: ' . rtrim(config('seo.canonical_base_url'), '/') . '/sitemap.xml',
            '',
        ]);

        return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $baseUrl = rtrim(config('seo.canonical_base_url'), '/');
        $urls = [$baseUrl . route('public.career.index', [], false)];
        $xmlUrls = collect($urls)->unique()->map(fn(string $url) => '    <url><loc>' . e($url) . '</loc></url>')->implode("\n");
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $xmlUrls . "\n</urlset>\n";

        return response($content, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
