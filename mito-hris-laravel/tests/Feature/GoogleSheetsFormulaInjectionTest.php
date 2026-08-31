<?php

namespace Tests\Feature;

use App\Services\Google\GoogleSheetsService;
use Mockery;
use Tests\TestCase;

class GoogleSheetsFormulaInjectionTest extends TestCase
{
    public function test_sanitize_row_prefixes_dangerous_formulas_for_sheets(): void
    {
        $service = new class extends GoogleSheetsService {
            public function __construct() {}

            public function exposeSanitizeRow(array $row): array
            {
                $method = new \ReflectionMethod(self::class, 'sanitizeRow');
                $method->setAccessible(true);

                return $method->invoke($this, $row);
            }
        };

        $result = $service->exposeSanitizeRow([
            '=HYPERLINK("https://evil.test","click")',
            '=IMPORTXML("https://evil.test", "//x")',
            '=SUM(A1:A2)',
            '+628123456789',
            '+62 812 3456 789',
            '-123',
            '@username',
            'plain text',
            null,
            true,
            false,
        ]);

        $this->assertSame([
            "'=HYPERLINK(\"https://evil.test\",\"click\")",
            "'=IMPORTXML(\"https://evil.test\", \"//x\")",
            "'=SUM(A1:A2)",
            '+628123456789',
            '+62 812 3456 789',
            '-123',
            '@username',
            'plain text',
            '',
            'TRUE',
            '',
        ], $result);
    }
}
