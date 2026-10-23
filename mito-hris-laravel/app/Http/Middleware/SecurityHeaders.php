<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecurityHeaders
{
    /**
     * Apply the response security headers.
     *
     * Script policy is enforced. Inline scripts must carry the per-request
     * nonce. Inline style attributes stay allowed because the Blade UI uses them.
     */
    public function handle(Request $request, Closure $next)
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=(), display-capture=(), fullscreen=()');
        $response->headers->set('Accept-CH', 'Sec-CH-UA-Platform-Version, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List, Sec-CH-UA-Arch, Sec-CH-UA-Bitness');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        $response->headers->set('Content-Security-Policy', $this->buildCsp($nonce));

        // Phase 3: HSTS — only on HTTPS to enforce secure transport
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    protected function buildCsp(string $nonce): string
    {
        $vite = $this->viteDevOrigins();
        $viteHttp = $vite['http'] ?? '';
        $viteWs = $vite['ws'] ?? '';
        $httpExtra = $viteHttp !== '' ? ' ' . $viteHttp : '';
        $connectExtra = $viteHttp !== '' ? ' ' . $viteHttp . ' ' . $viteWs : '';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com{$httpExtra}",
            "script-src-attr 'none'",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com{$httpExtra}",
            "img-src 'self' data: blob: https://cdn.jsdelivr.net https://images.unsplash.com{$httpExtra}",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com{$httpExtra}",
            "connect-src 'self'{$connectExtra}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }

    /**
     * Vite's dev server is another origin. Allow it only while public/hot points at loopback.
     *
     * @return array{http: string, ws: string}
     */
    private function viteDevOrigins(): array
    {
        $hot = public_path('hot');
        if (!is_file($hot)) {
            return [];
        }

        $origin = trim((string) file_get_contents($hot));
        $parts = parse_url($origin);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || !in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            return [];
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $wsScheme = $scheme === 'https' ? 'wss' : 'ws';
        $hosts = array_values(array_unique([$host, '127.0.0.1', 'localhost', '[::1]']));
        $http = [];
        $ws = [];
        foreach ($hosts as $loopback) {
            $http[] = $scheme . '://' . $loopback . $port;
            $ws[] = $wsScheme . '://' . $loopback . $port;
        }

        return [
            'http' => implode(' ', $http),
            'ws' => implode(' ', $ws),
        ];
    }
}
