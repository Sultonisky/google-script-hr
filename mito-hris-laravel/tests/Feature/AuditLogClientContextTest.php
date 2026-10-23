<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Database\AuditLogDatabaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuditLogClientContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_row_and_page_show_the_public_client_instead_of_the_docker_gateway(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogDatabaseRepository::class);
        $this->app->instance('request', Request::create('/logout', 'GET', [], [], [], [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '103.121.183.21, 172.18.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
            'HTTP_REFERER' => 'https://hris.mito.co.id/hr/recruitment/accepted',
        ]));

        $this->assertTrue(app(AuditLogRepositoryInterface::class)->log(
            'Authentication',
            'hr@mito.co.id',
            'logged_out',
            null,
            null,
            null,
            'hr@mito.co.id',
            'Authentication',
        ));

        $row = AuditLog::query()->firstOrFail();
        $this->assertSame('103.121.183.21', $row->ip_address);
        $this->assertSame('Google Chrome 155', $row->browser);
        $this->assertSame('Desktop · 64-bit', $row->device);
        $this->assertSame('Windows 10/11', $row->platform);
        $this->assertSame('https://hris.mito.co.id/hr/recruitment/accepted', $row->referer);

        Session::put('hr_user', [
            'email' => 'super.admin@mito.id',
            'fullName' => 'Super Admin User',
            'role' => 'Super Admin',
            'permissions' => config('hris.auth.role_permissions')['Super Admin'] ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);

        $response = $this->get('/hr/audit-logs');
        $html = $response->getContent();
        if ($response->status() !== 200) {
            $this->fail('HTTP '.$response->status().' '.substr(strip_tags($html), 0, 400));
        }
        foreach ([
            '<th>IP</th>',
            'Device / OS',
        ] as $absent) {
            if (str_contains($html, $absent)) {
                $this->fail('still visible: '.$absent);
            }
        }
        foreach ([
            'data-audit-detail',
            '103.121.183.21',
            'Google Chrome 155',
            'Desktop \u00b7 64-bit',
            'Windows 10\/11',
        ] as $present) {
            if (!str_contains($html, $present)) {
                $this->fail('missing from detail payload: '.$present);
            }
        }
    }
}
