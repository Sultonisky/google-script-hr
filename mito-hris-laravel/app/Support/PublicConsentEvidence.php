<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicConsentEvidence
{
    /**
     * @return array{
     *     stage: string,
     *     accepted: true,
     *     server: array{timestamp: string, ip: string, user_agent: string},
     *     client_claim: array{device: ?string, timestamp: mixed, latitude: mixed, longitude: mixed, location: ?string}
     * }
     */
    public static function capture(Request $request, string $stage): array
    {
        return [
            'stage' => $stage,
            'accepted' => true,
            'server' => [
                'timestamp' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ip' => (string) $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ],
            'client_claim' => [
                'device' => self::clip($request->input('consent_device', $request->input('device')), 120),
                'timestamp' => $request->input('consent_timestamp', $request->input('client_timestamp')),
                'latitude' => $request->input('consent_latitude', $request->input('latitude')),
                'longitude' => $request->input('consent_longitude', $request->input('longitude')),
                'location' => self::clip($request->input('consent_location', $request->input('location')), 120),
            ],
        ];
    }

    private static function clip(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return substr($text, 0, $limit);
    }
}
