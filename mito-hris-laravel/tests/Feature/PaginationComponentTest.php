<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationComponentTest extends TestCase
{
    #[Test]
    public function active_page_is_styled_when_pagination_window_is_near_the_end(): void
    {
        $html = view('components.pagination', [
            'currentPage' => 8,
            'total' => 150,
            'perPage' => 15,
            'route' => 'public.seo.robots',
        ])->render();

        $this->assertStringContainsString('class="page-btn  active "', $html);
    }
}
