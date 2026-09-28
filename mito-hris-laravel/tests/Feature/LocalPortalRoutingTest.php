<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Local development serves every portal from localhost using path prefixes
 * instead of dedicated subdomains, so the local route group is only
 * registered when APP_ENV=local.
 */
class LocalPortalRoutingTest extends TestCase
{
    private ?string $originalEnv = null;

    public function createApplication()
    {
        $this->originalEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null;
        $this->setAppEnv('local');

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->setAppEnv($this->originalEnv ?? 'testing');
    }

    private function setAppEnv(string $value): void
    {
        $_SERVER['APP_ENV'] = $value;
        $_ENV['APP_ENV'] = $value;
        putenv('APP_ENV=' . $value);
    }

    public function test_local_asset_portal_does_not_collide_with_public_assets_directory(): void
    {
        $this->assertTrue(is_dir(public_path('assets')));

        $path = parse_url(route('assets.portal.index'), PHP_URL_PATH);

        $this->assertNotSame('/assets', rtrim((string) $path, '/'));
        $this->assertFalse(file_exists(public_path(ltrim((string) $path, '/'))));
        $this->assertFalse(file_exists(public_path(ltrim((string) parse_url(route('assets.login'), PHP_URL_PATH), '/'))));
    }

    public function test_guest_is_redirected_to_asset_login(): void
    {
        $this->get('http://localhost' . parse_url(route('assets.portal.index'), PHP_URL_PATH))
            ->assertRedirect(route('assets.login'));
    }

    public function test_guest_is_redirected_to_certificate_login(): void
    {
        $this->get('http://localhost/certifications')
            ->assertRedirect(route('certificates.login'));
    }

    public function test_guest_is_redirected_to_mpr_login(): void
    {
        $this->get('http://localhost/mpr/request')
            ->assertRedirect(route('mpr.auth.login'));
    }

    public function test_dedicated_login_pages_render_locally(): void
    {
        $this->get('http://localhost' . parse_url(route('assets.login'), PHP_URL_PATH))->assertOk();
        $this->get('http://localhost/certifications/login')->assertOk();
        $this->get('http://localhost/mpr/login')->assertOk();
        $this->get('http://localhost/mpr')->assertOk();
    }

    public function test_hris_session_does_not_grant_local_certificate_access(): void
    {
        $this->withSession(['hr_user' => ['email' => 'admin@mito.id', 'role' => 'Admin', 'auth_domain' => 'users']])
            ->get('http://localhost/certifications')
            ->assertRedirect(route('certificates.login'));
    }

    public function test_hris_portal_links_to_local_dedicated_portals(): void
    {
        $this->assertTrue(Route::has('assets.portal.index'));

        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee('href="' . route('assets.portal.index') . '"', false)
            ->assertSee('href="' . route('certificates.portal.index') . '"', false)
            ->assertDontSee(config('hris.domains.assets'), false)
            ->assertDontSee(config('hris.domains.certificates'), false);
    }
}
