<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicApplicationProbe
{
    public const REASON_SCRIPT_USER_AGENT = 'user-agent non-browser';

    public const REASON_DISPOSABLE_EMAIL = 'email sekali pakai';

    public const REASON_TEST_NIK = 'NIK uji';

    /**
     * Known script and disposable-mail signals. A normal browser submission does not match.
     *
     * Pass $client only from the live HTTP request. Null keeps UA-only checks so a Chrome
     * string by itself is not treated as proof of a browser.
     *
     * @param  array{runtime?: string, fetch_site?: string, fetch_mode?: string, fetch_dest?: string}|null  $client
     * @return list<string>
     */
    public static function reasons(?string $userAgent, ?string $email, ?string $nik, ?array $client = null): array
    {
        $reasons = [];

        if (self::isScriptUserAgent($userAgent) || ($client !== null && self::isSpoofedBrowser($userAgent, $client))) {
            $reasons[] = self::REASON_SCRIPT_USER_AGENT;
        }

        if (self::isDisposableEmail($email)) {
            $reasons[] = self::REASON_DISPOSABLE_EMAIL;
        }

        if (self::isTestNik($nik)) {
            $reasons[] = self::REASON_TEST_NIK;
        }

        return $reasons;
    }

    /**
     * @param  list<string>  $reasons
     */
    public static function messageFor(array $reasons): string
    {
        if (in_array(self::REASON_SCRIPT_USER_AGENT, $reasons, true)) {
            return 'Pendaftaran tidak dapat diproses dari aplikasi ini. Gunakan browser.';
        }

        if (in_array(self::REASON_DISPOSABLE_EMAIL, $reasons, true)) {
            return self::emailMessage();
        }

        return self::nikMessage();
    }

    public static function emailMessage(): string
    {
        return 'Gunakan alamat email yang bisa dihubungi tim HR. Email sekali pakai tidak dapat digunakan.';
    }

    /**
     * @return list<string>
     */
    public static function disposableDomains(): array
    {
        static $domains;

        if (is_array($domains)) {
            return $domains;
        }

        $lines = file(__DIR__.'/disposable-email-domains.txt', FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return $domains = [];
        }

        $domains = [];
        foreach ($lines as $line) {
            $domain = strtolower(trim($line));
            if ($domain === '' || str_starts_with($domain, '#')) {
                continue;
            }
            $domains[] = $domain;
        }

        return $domains;
    }

    public static function nikMessage(): string
    {
        return 'NIK tidak valid. Periksa kembali 16 digit pada KTP.';
    }

    /**
     * Signals taken from the request, not from the User-Agent text the client can rewrite.
     *
     * @return array{runtime: string, fetch_site: string, fetch_mode: string, fetch_dest: string}
     */
    public static function clientContext(Request $request): array
    {
        $runtime = strtolower(trim((string) $request->input('client_runtime', '')));
        if (!in_array($runtime, ['browser', 'webdriver'], true)) {
            $runtime = '';
        }

        return [
            'runtime' => $runtime,
            'fetch_site' => self::headerValue($request, 'Sec-Fetch-Site'),
            'fetch_mode' => self::headerValue($request, 'Sec-Fetch-Mode'),
            'fetch_dest' => self::headerValue($request, 'Sec-Fetch-Dest'),
        ];
    }

    private static function isScriptUserAgent(?string $userAgent): bool
    {
        $ua = strtolower(trim((string) $userAgent));
        if ($ua === '') {
            return false;
        }

        foreach ([
            'curl/',
            'wget/',
            'python-requests',
            'python-urllib',
            'httpie/',
            'go-http-client',
            'scrapy',
            'libwww-perl',
            'sqlmap',
            'nikto',
            'nuclei',
            'zgrab',
            'masscan',
            'aiohttp',
            'okhttp',
            'java/',
            'headlesschrome',
            'playwright',
            'puppeteer',
            'phantomjs',
            'selenium',
            'webdriver',
            'slimerjs',
            'cypress',
            'node-fetch',
            'undici',
            'axios/',
            'python-httpx',
            'httpx/',
            'libhttp',
        ] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{runtime?: string, fetch_site?: string, fetch_mode?: string, fetch_dest?: string}  $client
     */
    private static function isSpoofedBrowser(?string $userAgent, array $client): bool
    {
        $runtime = strtolower(trim((string) ($client['runtime'] ?? '')));
        if ($runtime === 'webdriver') {
            return true;
        }

        if (!self::claimsBrowser($userAgent)) {
            return false;
        }

        if ($runtime !== 'browser') {
            return true;
        }

        if (self::isInAppBrowser($userAgent)) {
            return false;
        }

        return ($client['fetch_site'] ?? '') !== 'same-origin'
            || ($client['fetch_mode'] ?? '') !== 'navigate'
            || ($client['fetch_dest'] ?? '') !== 'document';
    }

    private static function claimsBrowser(?string $userAgent): bool
    {
        $ua = strtolower(trim((string) $userAgent));
        if ($ua === '') {
            return false;
        }

        foreach (['mozilla/', 'chrome/', 'chromium/', 'firefox/', 'safari/', 'edg/', 'opr/'] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isInAppBrowser(?string $userAgent): bool
    {
        $ua = strtolower(trim((string) $userAgent));
        foreach (['linkedin', 'whatsapp', 'instagram', 'fban', 'fbav', 'tiktok', 'musical_ly', '; wv', ' wv)'] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function headerValue(Request $request, string $name): string
    {
        return strtolower(trim((string) $request->headers->get($name, '')));
    }

    private static function isDisposableEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        $domain = rtrim(substr($email, $at + 1), '.');
        $labels = array_values(array_filter(explode('.', $domain), static fn (string $label): bool => $label !== ''));

        foreach (self::disposableDomains() as $blocked) {
            if (str_contains($blocked, '.')) {
                if ($domain === $blocked || str_ends_with($domain, '.' . $blocked)) {
                    return true;
                }
                continue;
            }

            if (in_array($blocked, $labels, true)) {
                return true;
            }
        }

        return false;
    }

    private static function isTestNik(?string $nik): bool
    {
        $digits = preg_replace('/\D+/', '', (string) $nik) ?? '';
        if (strlen($digits) !== 16) {
            return false;
        }

        if (preg_match('/^(\d)\1{15}$/', $digits) === 1) {
            return true;
        }

        return in_array($digits, ['1234567890123456', '0123456789012345'], true);
    }
}
