<?php

namespace App\Support;

class PublicApplicationProbe
{
    /**
     * Known script and disposable-mail signals. A normal browser submission does not match.
     *
     * @return list<string>
     */
    public static function reasons(?string $userAgent, ?string $email, ?string $nik): array
    {
        $reasons = [];

        if (self::isScriptUserAgent($userAgent)) {
            $reasons[] = 'user-agent non-browser';
        }

        if (self::isDisposableEmail($email)) {
            $reasons[] = 'email sekali pakai';
        }

        if (self::isTestNik($nik)) {
            $reasons[] = 'NIK uji';
        }

        return $reasons;
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

        $domain = substr($email, $at + 1);
        foreach ([
            'mailinator.com',
            'guerrillamail.com',
            'guerrillamail.info',
            'guerrillamail.biz',
            'guerrillamail.de',
            'guerrillamail.net',
            'guerrillamail.org',
            'guerrillamailblock.com',
            'sharklasers.com',
            'grr.la',
            '10minutemail.com',
            '10minutemail.net',
            'tempmail.com',
            'temp-mail.org',
            'yopmail.com',
            'yopmail.fr',
            'yopmail.net',
            'trashmail.com',
            'getnada.com',
            'inboxkitten.com',
            'dispostable.com',
            'mailnesia.com',
            'maildrop.cc',
            'throwawaymail.com',
            'mohmal.com',
            'emailondeck.com',
            'fakeinbox.com',
            'mintemail.com',
            'moakt.com',
            'tempail.com',
            'trash-mail.com',
            'mytemp.email',
        ] as $disposable) {
            if ($domain === $disposable || str_ends_with($domain, '.' . $disposable)) {
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
