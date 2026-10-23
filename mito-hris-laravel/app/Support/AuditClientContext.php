<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Client facts for an audit row, taken from the current HTTP request.
 * The Docker bridge address is not treated as the visitor.
 */
class AuditClientContext
{
    public function __construct(
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?string $browser,
        public readonly ?string $device,
        public readonly ?string $platform,
        public readonly ?string $referer,
    ) {}

    public static function capture(): self
    {
        if (!app()->bound('request')) {
            return self::empty();
        }

        $request = request();
        if (!$request instanceof Request) {
            return self::empty();
        }

        return self::fromRequest($request);
    }

    public static function fromRequest(Request $request): self
    {
        $userAgent = self::clip($request->userAgent(), 500);
        $ip = self::resolveIp(
            (string) $request->headers->get('x-forwarded-for', ''),
            (string) $request->headers->get('x-real-ip', ''),
            (string) $request->headers->get('cf-connecting-ip', ''),
            (string) $request->headers->get('true-client-ip', ''),
            (string) $request->server('REMOTE_ADDR', ''),
        );

        if ($ip !== null && self::isLoopback($ip) && $userAgent === null) {
            $ip = null;
        }

        if ($userAgent === null && $ip === null) {
            return self::empty();
        }

        $hints = self::hints($request);

        return new self(
            ipAddress: $ip,
            userAgent: $userAgent,
            browser: self::clip(self::browser($userAgent, $hints), 120),
            device: self::clip(self::device($userAgent, $hints), 120),
            platform: self::clip(self::platform($userAgent, $hints), 64),
            referer: self::httpReferer($request->headers->get('referer')),
        );
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, null);
    }

    /**
     * @return array{ip_address: ?string, user_agent: ?string, browser: ?string, device: ?string, platform: ?string, referer: ?string}
     */
    public function columns(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'browser' => $this->browser,
            'device' => $this->device,
            'platform' => $this->platform,
            'referer' => $this->referer,
        ];
    }

    /**
     * Prefer the rightmost public address in X-Forwarded-For so a spoofed
     * value on the left does not hide the client the proxy actually saw.
     * 172.18.0.1 and other private hops are skipped.
     */
    public static function resolveIp(
        string $forwardedFor,
        string $realIp = '',
        string $cloudflareIp = '',
        string $trueClientIp = '',
        string $remoteAddr = '',
    ): ?string {
        $forwarded = self::ipList($forwardedFor);
        for ($index = count($forwarded) - 1; $index >= 0; $index--) {
            if (self::isPublic($forwarded[$index])) {
                return $forwarded[$index];
            }
        }

        foreach ([$cloudflareIp, $trueClientIp, $realIp] as $candidate) {
            $ip = self::ipList($candidate)[0] ?? null;
            if ($ip !== null && self::isPublic($ip)) {
                return $ip;
            }
        }

        $remote = self::ipList($remoteAddr)[0] ?? null;
        if ($remote !== null && (self::isPublic($remote) || self::isLoopback($remote))) {
            return $remote;
        }

        return null;
    }

    /**
     * @param  array{brands?: array<string, string>, fullBrands?: array<string, string>}  $hints
     */
    public static function browser(?string $userAgent, array $hints = []): ?string
    {
        $brand = self::preferredBrand($hints['fullBrands'] ?? [])
            ?? self::preferredBrand($hints['brands'] ?? []);
        if ($brand !== null) {
            return self::clip($brand[0].' '.$brand[1], 120);
        }

        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $patterns = [
            'Microsoft Edge' => '/Edg(?:e|A|iOS)?\/(\d+(?:\.\d+){0,3})/',
            'Opera' => '/(?:OPR|Opera)\/(\d+(?:\.\d+){0,3})/',
            'Samsung Internet' => '/SamsungBrowser\/(\d+(?:\.\d+){0,3})/',
            'Google Chrome' => '/(?:Chrome|CriOS)\/(\d+(?:\.\d+){0,3})/',
            'Mozilla Firefox' => '/(?:Firefox|FxiOS)\/(\d+(?:\.\d+){0,3})/',
            'Safari' => '/Version\/(\d+(?:\.\d+){0,3}).+Safari\//',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent, $match) === 1) {
                return $name.' '.self::trimVersion($match[1]);
            }
        }

        return 'Lainnya';
    }

    /**
     * @param  array{mobile?: ?bool, model?: ?string, arch?: ?string, bitness?: ?string}  $hints
     */
    public static function device(?string $userAgent, array $hints = []): ?string
    {
        $userAgent ??= '';
        if ($userAgent === '' && $hints === []) {
            return null;
        }

        if ($userAgent !== '' && preg_match('/bot|crawl|spider|slurp|headless/i', $userAgent) === 1) {
            return 'Bot';
        }

        $form = self::formFactor($userAgent, $hints);
        $model = self::deviceModel($userAgent, $hints);
        if ($model !== null) {
            return self::clip($form === null ? $model : $form.' · '.$model, 120);
        }

        $architecture = self::architecture($userAgent, $hints);
        if ($form === 'Desktop' && $architecture !== null) {
            return 'Desktop · '.$architecture;
        }

        return $form;
    }

    /**
     * @param  array{platform?: ?string, platformVersion?: ?string}  $hints
     */
    public static function platform(?string $userAgent, array $hints = []): ?string
    {
        $userAgent ??= '';
        $name = strtolower((string) ($hints['platform'] ?? ''));
        $version = (string) ($hints['platformVersion'] ?? '');

        if ($name === 'windows' || str_contains($userAgent, 'Windows')) {
            return self::windowsName($userAgent, $version !== '' ? $version : null);
        }

        if ($name === 'android' || preg_match('/Android (\d+(?:\.\d+)?)/', $userAgent, $androidMatch) === 1) {
            $android = $version !== '' ? self::trimVersion($version) : ($androidMatch[1] ?? null);

            return $android !== null && $android !== '' ? 'Android '.$android : 'Android';
        }

        if ($name === 'ios' || preg_match('/(?:iPhone|iPad|CPU) OS (\d+(?:[_\.]\d+){0,2})/', $userAgent, $iosMatch) === 1) {
            $ios = $version !== ''
                ? self::trimVersion($version)
                : (isset($iosMatch[1]) ? str_replace('_', '.', $iosMatch[1]) : null);

            return $ios !== null && $ios !== '' ? 'iOS '.self::trimVersion($ios) : 'iOS';
        }

        if ($name === 'macos' || str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh')) {
            if ($version !== '') {
                return 'macOS '.self::trimVersion($version);
            }

            if (preg_match('/Mac OS X (\d+)[_\.](\d+)(?:[_\.](\d+))?/', $userAgent, $match) === 1
                && !($match[1] === '10' && $match[2] === '15' && str_contains($userAgent, 'Chrome/'))) {
                $mac = $match[1].'.'.$match[2].(isset($match[3]) && $match[3] !== '' ? '.'.$match[3] : '');

                return 'macOS '.self::trimVersion($mac);
            }

            return 'macOS';
        }

        if ($name === 'chrome os' || str_contains($userAgent, 'CrOS')) {
            return 'ChromeOS';
        }

        if ($name === 'linux' || str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return null;
    }

    /**
     * @return array{brands: array<string, string>, fullBrands: array<string, string>, mobile: ?bool, platform: ?string, platformVersion: ?string, model: ?string, arch: ?string, bitness: ?string}
     */
    private static function hints(Request $request): array
    {
        return [
            'brands' => self::clientBrands((string) $request->headers->get('sec-ch-ua', '')),
            'fullBrands' => self::clientBrands((string) $request->headers->get('sec-ch-ua-full-version-list', '')),
            'mobile' => self::boolHint($request->headers->get('sec-ch-ua-mobile')),
            'platform' => self::unquote($request->headers->get('sec-ch-ua-platform')),
            'platformVersion' => self::unquote($request->headers->get('sec-ch-ua-platform-version')),
            'model' => self::unquote($request->headers->get('sec-ch-ua-model')),
            'arch' => self::unquote($request->headers->get('sec-ch-ua-arch')),
            'bitness' => self::unquote($request->headers->get('sec-ch-ua-bitness')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function clientBrands(string $header): array
    {
        $brands = [];
        if (preg_match_all('/"([^"]+)";v="([^"]+)"/', $header, $matches, PREG_SET_ORDER) < 1) {
            return $brands;
        }

        foreach ($matches as $match) {
            $brands[$match[1]] = $match[2];
        }

        return $brands;
    }

    /**
     * @param  array<string, string>  $brands
     * @return array{0: string, 1: string}|null
     */
    private static function preferredBrand(array $brands): ?array
    {
        $chosen = null;
        foreach ($brands as $name => $version) {
            if (strcasecmp($name, 'Chromium') === 0 || preg_match('/^Not[_.A\/ ]*A[_. ]*Brand$/i', $name) === 1) {
                continue;
            }

            $chosen = [$name, self::trimVersion($version)];
            if (preg_match('/Chrome|Edge|Opera|Brave|Firefox|Safari|Samsung/i', $name) === 1) {
                return $chosen;
            }
        }

        return $chosen;
    }

    /**
     * @param  array{mobile?: ?bool}  $hints
     */
    private static function formFactor(string $userAgent, array $hints): ?string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $userAgent) === 1) {
            return 'Tablet';
        }

        if (preg_match('/Android/i', $userAgent) === 1 && preg_match('/Mobile/i', $userAgent) !== 1 && ($hints['mobile'] ?? null) !== true) {
            return 'Tablet';
        }

        if (($hints['mobile'] ?? null) === true || preg_match('/Mobile|Android|iPhone|iPod/i', $userAgent) === 1) {
            return 'Mobile';
        }

        if (($hints['mobile'] ?? null) === false || $userAgent !== '') {
            return 'Desktop';
        }

        return null;
    }

    /**
     * @param  array{model?: ?string}  $hints
     */
    private static function deviceModel(string $userAgent, array $hints): ?string
    {
        $model = trim((string) ($hints['model'] ?? ''));
        if ($model !== '') {
            return $model;
        }

        if (preg_match('/iPad/', $userAgent) === 1) {
            return 'iPad';
        }

        if (preg_match('/iPhone/', $userAgent) === 1) {
            return 'iPhone';
        }

        if (preg_match('/Android [^;)]*;\s*([^;)]+)/', $userAgent, $match) === 1) {
            $model = trim($match[1]);
            if ($model !== '' && !in_array(strtolower($model), ['mobile', 'k', 'linux'], true) && !str_starts_with($model, 'Build/')) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @param  array{arch?: ?string, bitness?: ?string}  $hints
     */
    private static function architecture(string $userAgent, array $hints): ?string
    {
        $bitness = (string) ($hints['bitness'] ?? '');
        $arch = strtolower((string) ($hints['arch'] ?? ''));
        if ($bitness === '64' && ($arch === '' || $arch === 'x86')) {
            return '64-bit';
        }

        if ($arch === 'arm') {
            return $bitness === '64' ? 'ARM64' : 'ARM';
        }

        if (preg_match('/Win64|x64|WOW64|x86_64|amd64/i', $userAgent) === 1) {
            return '64-bit';
        }

        if (preg_match('/ARM64|aarch64/i', $userAgent) === 1) {
            return 'ARM64';
        }

        return null;
    }

    private static function windowsName(string $userAgent, ?string $platformVersion): string
    {
        if ($platformVersion !== null && preg_match('/^(\d+)(?:\.(\d+))?/', $platformVersion, $match) === 1) {
            $major = (int) $match[1];
            if ($major >= 13) {
                return 'Windows 11';
            }

            if ($major >= 1) {
                return 'Windows 10';
            }

            $minor = (int) ($match[2] ?? 0);
            if ($minor >= 3) {
                return 'Windows 8.1';
            }

            if ($minor === 2) {
                return 'Windows 8';
            }

            return 'Windows 7';
        }

        if (preg_match('/Windows NT 10\.0/', $userAgent) === 1) {
            return 'Windows 10/11';
        }

        if (preg_match('/Windows NT 6\.3/', $userAgent) === 1) {
            return 'Windows 8.1';
        }

        if (preg_match('/Windows NT 6\.1/', $userAgent) === 1) {
            return 'Windows 7';
        }

        return 'Windows';
    }

    private static function trimVersion(string $version): string
    {
        $version = trim($version);
        $version = preg_replace('/(?:\.0)+$/', '', $version) ?? $version;

        return $version === '' ? '0' : $version;
    }

    private static function boolHint(mixed $value): ?bool
    {
        $text = trim((string) $value);
        if ($text === '?1') {
            return true;
        }

        if ($text === '?0') {
            return false;
        }

        return null;
    }

    private static function unquote(mixed $value): ?string
    {
        $text = trim((string) $value);
        $text = trim($text, "\"'");
        if ($text === '') {
            return null;
        }

        return $text;
    }

    /**
     * @return list<string>
     */
    private static function ipList(string $value): array
    {
        $ips = [];
        foreach (preg_split('/\s*,\s*/', trim($value)) ?: [] as $part) {
            $part = trim($part, " \t\"'[]");
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_IP) !== false) {
                $ips[] = $part;
            }
        }

        return $ips;
    }

    private static function isPublic(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private static function isLoopback(string $ip): bool
    {
        return $ip === '127.0.0.1' || $ip === '::1';
    }

    private static function httpReferer(mixed $value): ?string
    {
        $referer = self::clip($value, 1000);
        if ($referer === null || preg_match('#^https?://#i', $referer) !== 1) {
            return null;
        }

        $parts = parse_url($referer);
        if (!is_array($parts) || !isset($parts['host']) || $parts['host'] === '') {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }

        return self::clip($scheme.'://'.$parts['host'].$port.$path, 500);
    }

    private static function clip(mixed $value, int $limit): ?string
    {
        $text = trim((string) $value);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? '';
        if ($text === '') {
            return null;
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_scrub($text, 'UTF-8');
        }

        return mb_substr($text, 0, $limit, 'UTF-8');
    }
}
