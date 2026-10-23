<?php

namespace Tests\Unit\Support;

use App\Support\AuditClientContext;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditClientContextTest extends TestCase
{
    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36';

    public function test_docker_gateway_is_not_stored_when_a_public_forwarded_ip_exists(): void
    {
        $ip = AuditClientContext::resolveIp(
            '1.2.3.4, 103.121.183.21, 172.18.0.1',
            '',
            '',
            '',
            '172.18.0.1',
        );

        $this->assertSame('103.121.183.21', $ip);
    }

    public function test_real_ip_header_is_used_when_forwarded_for_is_only_the_gateway(): void
    {
        $ip = AuditClientContext::resolveIp('', '103.121.183.21', '', '', '172.18.0.1');

        $this->assertSame('103.121.183.21', $ip);
    }

    public function test_gateway_alone_is_not_recorded_as_the_client(): void
    {
        $this->assertNull(AuditClientContext::resolveIp('', '', '', '', '172.18.0.1'));
    }

    public function test_browser_device_and_platform_come_from_the_user_agent(): void
    {
        $request = Request::create('/logout', 'GET', [], [], [], [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '103.121.183.21',
            'HTTP_USER_AGENT' => self::CHROME_WINDOWS,
            'HTTP_REFERER' => 'https://hris.mito.co.id/hr/recruitment/accepted',
        ]);

        $context = AuditClientContext::fromRequest($request);

        $this->assertSame('103.121.183.21', $context->ipAddress);
        $this->assertSame('Google Chrome 155', $context->browser);
        $this->assertSame('Desktop · 64-bit', $context->device);
        $this->assertSame('Windows 10/11', $context->platform);
        $this->assertSame('https://hris.mito.co.id/hr/recruitment/accepted', $context->referer);
    }

    public function test_referer_keeps_the_page_and_drops_query_secrets(): void
    {
        $request = Request::create('/logout', 'GET', [], [], [], [
            'REMOTE_ADDR' => '103.121.183.21',
            'HTTP_USER_AGENT' => self::CHROME_WINDOWS,
            'HTTP_REFERER' => 'https://hris.mito.co.id/hr/employees?search=vina&token=secret#row',
        ]);

        $context = AuditClientContext::fromRequest($request);

        $this->assertSame('https://hris.mito.co.id/hr/employees', $context->referer);
    }

    public function test_phone_user_agent_is_classified_as_mobile(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36';

        $this->assertSame('Google Chrome 155', AuditClientContext::browser($ua));
        $this->assertSame('Mobile · Pixel 8', AuditClientContext::device($ua));
        $this->assertSame('Android 14', AuditClientContext::platform($ua));
    }

    public function test_client_hints_name_the_browser_windows_11_and_phone_model(): void
    {
        $windows = Request::create('/logout', 'GET', [], [], [], [
            'REMOTE_ADDR' => '103.121.183.21',
            'HTTP_USER_AGENT' => self::CHROME_WINDOWS,
            'HTTP_SEC_CH_UA' => '"Google Chrome";v="155", "Chromium";v="155", "Not.A/Brand";v="99"',
            'HTTP_SEC_CH_UA_FULL_VERSION_LIST' => '"Google Chrome";v="155.0.7632.76", "Chromium";v="155.0.7632.76", "Not.A/Brand";v="99.0.0.0"',
            'HTTP_SEC_CH_UA_PLATFORM' => '"Windows"',
            'HTTP_SEC_CH_UA_PLATFORM_VERSION' => '"15.0.0"',
            'HTTP_SEC_CH_UA_MOBILE' => '?0',
            'HTTP_SEC_CH_UA_ARCH' => '"x86"',
            'HTTP_SEC_CH_UA_BITNESS' => '"64"',
        ]);
        $desktop = AuditClientContext::fromRequest($windows);
        $this->assertSame('Google Chrome 155.0.7632.76', $desktop->browser);
        $this->assertSame('Desktop · 64-bit', $desktop->device);
        $this->assertSame('Windows 11', $desktop->platform);

        $phone = Request::create('/hr/dashboard', 'GET', [], [], [], [
            'REMOTE_ADDR' => '103.121.183.21',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36',
            'HTTP_SEC_CH_UA' => '"Google Chrome";v="155", "Chromium";v="155"',
            'HTTP_SEC_CH_UA_MOBILE' => '?1',
            'HTTP_SEC_CH_UA_MODEL' => '"Pixel 8"',
            'HTTP_SEC_CH_UA_PLATFORM' => '"Android"',
            'HTTP_SEC_CH_UA_PLATFORM_VERSION' => '"14.0.0"',
        ]);
        $mobile = AuditClientContext::fromRequest($phone);
        $this->assertSame('Google Chrome 155', $mobile->browser);
        $this->assertSame('Mobile · Pixel 8', $mobile->device);
        $this->assertSame('Android 14', $mobile->platform);
    }

    public function test_edge_and_firefox_keep_their_own_names(): void
    {
        $this->assertSame(
            'Microsoft Edge 155.0.3497.81',
            AuditClientContext::browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.3497.81')
        );
        $this->assertSame(
            'Mozilla Firefox 128.0.1',
            AuditClientContext::browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0.1')
        );
    }
}
