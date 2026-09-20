<?php

namespace Tests\Unit;

use App\Services\EmployeeIdGenerator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmployeeIdGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_generate_uses_join_date_and_increments_sequence(): void
    {
        $generator = new EmployeeIdGenerator();

        $this->assertSame('2026011501', $generator->generate('2026-01-15'));
        $this->assertSame('2026011502', $generator->generate('2026-01-15'));
        $this->assertSame('2026030101', $generator->generate('2026-03-01'));
    }

    public function test_generate_continues_after_existing_ids_for_the_same_join_date(): void
    {
        $generator = new EmployeeIdGenerator();

        $id = $generator->generate('2026-01-15', [
            '2026011501',
            '2026011507',
            '2026030101',
            'EMP-OS-2026-1111',
        ]);

        $this->assertSame('2026011508', $id);
    }
}
