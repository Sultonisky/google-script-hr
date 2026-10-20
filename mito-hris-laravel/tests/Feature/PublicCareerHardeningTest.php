<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicCareerHardeningTest extends TestCase
{
    public function test_robots_file_is_the_only_served_list(): void
    {
        $response = $this->get(route('public.seo.robots'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Allow: /', false);
        $response->assertDontSee('Disallow:', false);
        $response->assertDontSee('Sitemap:', false);
    }

    public function test_unused_region_endpoints_are_not_registered(): void
    {
        $this->getJson('/api/v1/provinces')->assertNotFound();
        $this->getJson('/api/v1/cities/31')->assertNotFound();
        $this->getJson('/api/v1/districts/3171')->assertNotFound();
        $this->postJson('/api/v1/nik/parse', ['nik' => '3175030101900002'])->assertNotFound();
    }
}
