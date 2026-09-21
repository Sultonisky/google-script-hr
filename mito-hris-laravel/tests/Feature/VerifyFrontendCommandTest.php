<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerifyFrontendCommandTest extends TestCase
{
    public function test_command_reports_vite_build_status(): void
    {
        $manifest = public_path('build/manifest.json');
        $result = $this->artisan('mito:verify-frontend');

        if (is_file($manifest)) {
            $result->assertSuccessful();
            $this->artisan('mito:verify-frontend', ['--build-path' => true])
                ->expectsOutputToContain(public_path('build'))
                ->assertSuccessful();

            return;
        }

        $result->assertFailed();
    }
}
