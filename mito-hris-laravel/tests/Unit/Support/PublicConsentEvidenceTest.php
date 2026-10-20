<?php

namespace Tests\Unit\Support;

use App\Support\PublicApplicationProbe;
use App\Support\PublicConsentEvidence;
use Illuminate\Http\Request;
use Tests\TestCase;

class PublicConsentEvidenceTest extends TestCase
{
    public function test_audit_keeps_server_facts_separate_from_client_claims(): void
    {
        $request = Request::create('/career/consent', 'POST', [
            'consent' => '1',
            'consent_device' => 'Mozilla/5.0 TestDevice',
            'consent_timestamp' => '1792399312000',
            'consent_latitude' => '-6.2',
            'consent_longitude' => '106.8',
            'consent_location' => 'Jakarta',
        ], [], [], [
            'REMOTE_ADDR' => '85.137.56.190',
            'HTTP_USER_AGENT' => 'curl/7.81.0',
        ]);

        $evidence = PublicConsentEvidence::capture($request, 'landing_consent');

        $this->assertSame('landing_consent', $evidence['stage']);
        $this->assertTrue($evidence['accepted']);
        $this->assertSame('85.137.56.190', $evidence['server']['ip']);
        $this->assertSame('curl/7.81.0', $evidence['server']['user_agent']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $evidence['server']['timestamp']);
        $this->assertSame('Mozilla/5.0 TestDevice', $evidence['client_claim']['device']);
        $this->assertSame('1792399312000', $evidence['client_claim']['timestamp']);
        $this->assertSame('-6.2', $evidence['client_claim']['latitude']);
        $this->assertSame('106.8', $evidence['client_claim']['longitude']);
        $this->assertSame('Jakarta', $evidence['client_claim']['location']);
    }

    public function test_probe_reasons_match_the_recorded_pentest_and_ignore_a_browser(): void
    {
        $this->assertSame(
            ['user-agent non-browser', 'email sekali pakai'],
            PublicApplicationProbe::reasons('curl/7.81.0', 'pentestprobe2026@inboxkitten.com', '3175030101900002')
        );
        $this->assertSame(
            ['NIK uji'],
            PublicApplicationProbe::reasons(
                'Mozilla/5.0',
                'pelamar@example.com',
                '1111111111111111'
            )
        );
        $this->assertSame(
            [],
            PublicApplicationProbe::reasons(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'pelamar@example.com',
                '3174100205050003'
            )
        );
        $this->assertSame(
            ['email sekali pakai'],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@1secmail.com', '3174100205050003')
        );
        $this->assertSame(
            ['email sekali pakai'],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@sub.yopmail.com', '3174100205050003')
        );
        $this->assertSame(
            ['email sekali pakai'],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@yopmail.fr', '3174100205050003')
        );
        $this->assertSame(
            ['email sekali pakai'],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@mail.tm', '3174100205050003')
        );
        $this->assertSame(
            [],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@mail.com', '3174100205050003')
        );
        $this->assertSame(
            ['email sekali pakai'],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@gmailnator.com', '3174100205050003')
        );
        $this->assertSame(
            [],
            PublicApplicationProbe::reasons('Mozilla/5.0', 'user@gmail.com', '3174100205050003')
        );
    }
}
