<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base TestCase for MITO HRIS.
 *
 * Domain architecture:
 *   HRIS        → config('hris.domains.hris')        e.g. hrismitogroup.web.id
 *   MPR         → config('hris.domains.mpr')          e.g. mpr.hrismitogroup.web.id
 *   Recruitment → config('hris.domains.recruitment')  e.g. recruitment.hrismitogroup.web.id
 *   Outsource   → config('hris.domains.outsource')    e.g. outsource.hrismitogroup.web.id
 *
 * All routes are domain-bound (Route::domain(...)). Bare-path requests such as
 * $this->get('/hr/dashboard') resolve to 404 by default because there is no
 * host context for the router to match.
 *
 * Fix: override prepareUrlForRequest() to prefix bare paths with the currently
 * active test domain. The default domain is HRIS — the domain that owns all
 * /hr/*, /login, /logout, /portal routes.
 *
 * To test routes on a different domain within a single test method, call
 * $this->onDomain('mpr') before the request. Domain is reset to HRIS at
 * the start of every test via setUp().
 *
 * Tests that already pass a full URL (http://domain/path) are unaffected
 * because prepareUrlForRequest only substitutes on bare paths.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * The domain key currently active for path-only requests.
     * One of: 'hris' | 'mpr' | 'recruitment' | 'outsource'
     */
    protected string $activeDomainKey = 'hris';

    protected function setUp(): void
    {
        parent::setUp();

        // Reset to HRIS domain before every test method.
        $this->activeDomainKey = 'hris';
    }

    /**
     * Switch the active domain for subsequent bare-path requests in this test.
     *
     * Usage:
     *   $this->onDomain('mpr')->get('/mpr/request');
     *   $this->onDomain('recruitment')->get('/apply');
     *   $this->onDomain('hris')->get('/hr/dashboard');
     */
    public function onDomain(string $domainKey): static
    {
        $this->activeDomainKey = $domainKey;

        return $this;
    }

    /**
     * Override: prefix bare paths with the active test domain so that
     * Route::domain() groups resolve correctly without requiring every
     * test call to spell out the full URL.
     *
     * Full URLs (starting with http:// or https://) are passed through
     * unchanged — PortalDomainIsolationTest uses these directly.
     */
    protected function prepareUrlForRequest($uri): string
    {
        $uri = $uri instanceof \Illuminate\Support\Uri ? $uri->value() : $uri;

        // Full URL — let Laravel handle it as-is.
        if (str_starts_with((string) $uri, 'http://') || str_starts_with((string) $uri, 'https://')) {
            return rtrim((string) $uri, '/');
        }

        // Bare path: inject the active domain so Route::domain() can match.
        $domain = config('hris.domains.' . $this->activeDomainKey, 'hrismitogroup.web.id');
        $path   = ltrim((string) $uri, '/');

        return 'http://' . $domain . ($path !== '' ? '/' . $path : '');
    }
}
