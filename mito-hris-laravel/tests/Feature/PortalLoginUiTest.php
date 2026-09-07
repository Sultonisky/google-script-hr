<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Portal Login UI Parity Tests
 *
 * Contract under test:
 *
 *   HRIS          hrismitogroup.web.id/login              → view `auth.login`
 *   Assets        assets.hrismitogroup.web.id/login       → view `auth.login`
 *   Certificates  certificates.hrismitogroup.web.id/login → view `auth.login`
 *
 * There is exactly ONE login view (`resources/views/auth/login.blade.php`) and
 * it is the visual source of truth. The Asset and Certificate portals reuse it
 * through the view's own parameter mechanism ($loginTitle, $loginSubtitle,
 * $loginPostUrl, $loginRedirectDefault) — no duplicated login Blade exists and
 * none may be introduced. These tests lock that contract so the portals can
 * never drift back to a separate login design.
 *
 * Portal-specific authentication is preserved untouched: dedicated
 * `asset_auth` / `certificate_auth` sessions, `access_assets_portal` /
 * `access_certificates_portal` gates (config hris.auth.dedicated_portal_roles),
 * and 403 for unauthorized roles.
 */
class PortalLoginUiTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function mockUserRepo(?array $user, bool $expectLastLogin = false): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null) {
            // updateLastLogin is invoked INSIDE AuthService::attemptLogin on every
            // successful credential check — including requests that are denied
            // afterwards by portal authorization — so the mock must always allow it.
            $expectation = $repo->shouldReceive('updateLastLogin');
            $expectLastLogin ? $expectation->once() : $expectation->zeroOrMoreTimes();
        }
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    private function makeUserRow(string $role, string $email): array
    {
        return [
            'Email'         => $email,
            'Username'      => explode('@', $email)[0],
            'Full Name'     => str_replace('_', ' ', $role) . ' Test',
            'Role'          => $role,
            'Status'        => 'Active',
            'Password Hash' => Hash::make('password-rahasia'),
        ];
    }

    /**
     * Assert the response IS the shared HRIS login view with its full signature
     * markup (logo, card, form, password toggle, remember-me, footer, CSRF).
     * Only the portal-provided card title/subtitle may differ.
     */
    private function assertSharedHrisLoginMarkup(
        TestResponse $response,
        string $expectedCardTitle,
        string $expectedCardSubtitle
    ): void {
        // ONE shared login view for all three portals — no duplicates allowed.
        $response->assertViewIs('auth.login');

        $html = $response->getContent();

        $sharedMarkers = [
            'id="loginForm"',
            'id="loginIdentifier"',
            'id="loginPassword"',
            'id="togglePassword"',
            'id="loginRememberMe"',
            'Ingat Saya',
            'login-logo',
            'mito-red.png',
            'login-card-title',
            'btn-login',
            'loginTransitionOverlay',
            'login-footer',
            'name="_token"',
        ];

        foreach ($sharedMarkers as $marker) {
            $this->assertStringContainsString(
                $marker,
                $html,
                "Shared HRIS login markup marker missing: {$marker}"
            );
        }

        // Portal title/context mechanism built into the shared view is preserved.
        $this->assertStringContainsString($expectedCardTitle, $html);
        $this->assertStringContainsString($expectedCardSubtitle, $html);
    }

    /**
     * Assert the shared login form posts (method=POST) to a URL on the expected
     * host — each portal submits to its own domain, never to the HRIS domain.
     */
    private function assertFormPostsToHost(TestResponse $response, string $expectedHost): void
    {
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<form id="loginForm"[^>]*>/',
            $html,
            'Shared login form tag not found.'
        );

        preg_match('/<form id="loginForm"[^>]*>/', $html, $matches);
        $formTag = $matches[0] ?? '';

        $this->assertStringContainsString('method="POST"', $formTag, 'Login form must remain method=POST.');
        $this->assertMatchesRegularExpression(
            '/action="https?:\/\/[^"]*' . preg_quote($expectedHost, '/') . '\/login"/',
            $formTag,
            "Login form must submit to {$expectedHost}/login."
        );
    }

    private function assertPostOnlyRoute(string $routeName): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "Route [{$routeName}] must exist.");
        $this->assertContains('POST', $route->methods(), "Route [{$routeName}] must accept POST.");
        $this->assertNotContains('GET', $route->methods(), "Route [{$routeName}] must not accept GET.");
    }

    // =========================================================================
    // 1-4. All three login pages render the SAME shared HRIS login view
    // =========================================================================

    #[Test]
    public function hris_login_page_renders_shared_hris_login_view(): void
    {
        $response = $this->get('http://hrismitogroup.web.id/login');

        $response->assertOk();
        $this->assertSharedHrisLoginMarkup(
            $response,
            'Masuk ke Sistem',
            'Gunakan email/username dan password Anda'
        );
        $this->assertFormPostsToHost($response, 'hrismitogroup.web.id');
    }

    #[Test]
    public function assets_login_page_renders_shared_hris_login_view(): void
    {
        $response = $this->get('http://assets.hrismitogroup.web.id/login');

        $response->assertOk();
        $this->assertSharedHrisLoginMarkup(
            $response,
            'Portal Aset',
            'Masuk untuk mengelola aset perusahaan'
        );
        $this->assertFormPostsToHost($response, 'assets.hrismitogroup.web.id');
    }

    #[Test]
    public function certificates_login_page_renders_shared_hris_login_view(): void
    {
        $response = $this->get('http://certificates.hrismitogroup.web.id/login');

        $response->assertOk();
        $this->assertSharedHrisLoginMarkup(
            $response,
            'Portal Sertifikasi',
            'Masuk untuk mengelola sertifikasi karyawan'
        );
        $this->assertFormPostsToHost($response, 'certificates.hrismitogroup.web.id');
    }

    // =========================================================================
    // 5-6. Portal login forms submit to their own POST authentication route
    // =========================================================================

    #[Test]
    public function assets_and_certificates_login_routes_are_post_only(): void
    {
        $this->assertPostOnlyRoute('assets.login.post');
        $this->assertPostOnlyRoute('certificates.login.post');
        $this->assertPostOnlyRoute('login.post'); // HRIS POST route unchanged.
    }

    // =========================================================================
    // 7. HRIS login behavior remains unchanged
    // =========================================================================

    #[Test]
    public function hris_successful_login_lands_on_hris_dashboard(): void
    {
        $this->mockUserRepo($this->makeUserRow('Admin', 'admin@example.test'), true);

        $response = $this->postJson('http://hrismitogroup.web.id/login', [
            'identifier' => 'admin@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('hr.dashboard'));
        $this->assertTrue($this->app['session']->has('hr_user'));
        $this->assertSame('hris', $this->app['session']->get('hr_user')['portal']);
    }

    #[Test]
    public function hris_failed_login_keeps_generic_error(): void
    {
        $this->mockUserRepo($this->makeUserRow('Admin', 'admin@example.test'));

        $response = $this->postJson('http://hrismitogroup.web.id/login', [
            'identifier' => 'admin@example.test',
            'password'   => 'password-salah',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Email/username atau password salah.');
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    // =========================================================================
    // 8. Assets successful login stays on the Assets domain
    // =========================================================================

    #[Test]
    public function assets_successful_login_stays_on_assets_domain(): void
    {
        $this->mockUserRepo($this->makeUserRow('Admin', 'admin@example.test'), true);

        $response = $this->postJson('http://assets.hrismitogroup.web.id/login', [
            'identifier' => 'admin@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // JSON redirect target (used by the shared view's JS) must stay on assets.
        $redirect = $response->json('redirect');
        $this->assertSame(route('assets.portal.index'), $redirect);
        $this->assertStringContainsString('assets.hrismitogroup.web.id', $redirect);

        // Dedicated portal session must carry the assets context.
        $user = $this->app['session']->get('asset_auth');
        $this->assertNotNull($user);
        $this->assertSame('assets', $user['portal']);
        $this->assertSame('assets', $user['auth_domain']);
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    #[Test]
    public function assets_web_form_login_redirects_to_assets_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow('Admin', 'admin@example.test'), true);

        // Render the login page first and submit the real CSRF token, mirroring
        // the actual form semantics (GET page → POST with _token).
        $page = $this->get('http://assets.hrismitogroup.web.id/login');
        preg_match('/name="_token" value="([^"]+)"/', $page->getContent(), $token);

        $response = $this->post('http://assets.hrismitogroup.web.id/login', [
            '_token'     => $token[1] ?? '',
            'identifier' => 'admin@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString(
            'assets.hrismitogroup.web.id',
            (string) $response->headers->get('Location'),
            'Assets login must stay on the Assets domain.'
        );
    }

    #[Test]
    public function assets_logged_in_user_visiting_login_is_sent_to_assets_portal(): void
    {
        $this->app['session']->put('asset_auth', [
            'email'       => 'admin@example.test',
            'role'        => 'Admin',
            'auth_domain' => 'assets',
            'portal'      => 'assets',
        ]);

        $this->get('http://assets.hrismitogroup.web.id/login')
            ->assertRedirect(route('assets.portal.index'));
    }

    // =========================================================================
    // 9. Certificates successful login stays on the Certificates domain
    // =========================================================================

    #[Test]
    public function certificates_successful_login_stays_on_certificates_domain(): void
    {
        $this->mockUserRepo($this->makeUserRow('Admin', 'admin@example.test'), true);

        $response = $this->postJson('http://certificates.hrismitogroup.web.id/login', [
            'identifier' => 'admin@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $redirect = $response->json('redirect');
        $this->assertSame(route('certificates.portal.index'), $redirect);
        $this->assertStringContainsString('certificates.hrismitogroup.web.id', $redirect);

        $user = $this->app['session']->get('certificate_auth');
        $this->assertNotNull($user);
        $this->assertSame('certificates', $user['portal']);
        $this->assertSame('certificates', $user['auth_domain']);
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    #[Test]
    public function certificates_logged_in_user_visiting_login_is_sent_to_certificates_portal(): void
    {
        $this->app['session']->put('certificate_auth', [
            'email'       => 'admin@example.test',
            'role'        => 'Admin',
            'auth_domain' => 'certificates',
            'portal'      => 'certificates',
        ]);

        $this->get('http://certificates.hrismitogroup.web.id/login')
            ->assertRedirect(route('certificates.portal.index'));
    }

    // =========================================================================
    // 10. Existing authorization behavior remains intact
    // =========================================================================

    #[Test]
    public function role_without_portal_access_cannot_login_to_assets_portal(): void
    {
        // 'User' is not in config hris.auth.dedicated_portal_roles.assets —
        // authentication succeeds but authorization must deny with 403 and no session.
        $this->mockUserRepo($this->makeUserRow('User', 'user@example.test'));

        $response = $this->postJson('http://assets.hrismitogroup.web.id/login', [
            'identifier' => 'user@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Anda tidak memiliki akses ke Portal Aset.');
        $this->assertFalse($this->app['session']->has('asset_auth'));
    }

    #[Test]
    public function role_without_portal_access_cannot_login_to_certificates_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow('User', 'user@example.test'));

        $response = $this->postJson('http://certificates.hrismitogroup.web.id/login', [
            'identifier' => 'user@example.test',
            'password'   => 'password-rahasia',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Anda tidak memiliki akses ke Portal Sertifikasi.');
        $this->assertFalse($this->app['session']->has('certificate_auth'));
    }

    #[Test]
    public function asset_portal_middleware_still_returns_403_for_session_without_gate(): void
    {
        // A valid-shape asset_auth session whose role lacks the portal gate must
        // still be denied by the (untouched) portal access middleware.
        $this->app['session']->put('asset_auth', [
            'email'       => 'user@example.test',
            'role'        => 'User',
            'auth_domain' => 'assets',
            'portal'      => 'assets',
        ]);

        // Portal index on the assets domain is GET / (route assets.portal.index);
        // the untouched middleware must deny this session before any controller runs.
        $this->get('http://assets.hrismitogroup.web.id/')->assertForbidden();
    }

    #[Test]
    public function asset_portal_middleware_still_redirects_visitors_without_session(): void
    {
        $this->get('http://assets.hrismitogroup.web.id/')
            ->assertRedirect(route('assets.login'));

        $this->get('http://certificates.hrismitogroup.web.id/certifications')
            ->assertRedirect(route('certificates.login'));
    }
}
