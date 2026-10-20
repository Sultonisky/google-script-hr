<?php

namespace App\Support;

class PublicApplicationProbe
{
    public const REASON_SCRIPT_USER_AGENT = 'user-agent non-browser';

    public const REASON_DISPOSABLE_EMAIL = 'email sekali pakai';

    public const REASON_TEST_NIK = 'NIK uji';

    /**
     * Known script and disposable-mail signals. A normal browser submission does not match.
     *
     * @return list<string>
     */
    public static function reasons(?string $userAgent, ?string $email, ?string $nik): array
    {
        $reasons = [];

        if (self::isScriptUserAgent($userAgent)) {
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
        ] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
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
