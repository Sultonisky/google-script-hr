<?php

namespace Tests\Feature;

use App\Services\Google\GoogleSheetsService;
use Mockery;
use Tests\TestCase;

class GoogleSheetsFormulaInjectionTest extends TestCase
{
    public function test_sanitize_row_preserves_exact_strings_for_raw_sheets_writes(): void
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
            '3201014708830005',
            '84.302.732.7-403.000',
            '6830520081',
            '20063096133',
            '0003051435148',
            '0001128024865',
            '0600485083',
            '+6287884042777',
            "O'Connor",
            "D'Angelo",
            "L'Equipe",
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
            '3201014708830005',
            '84.302.732.7-403.000',
            '6830520081',
            '20063096133',
            '0003051435148',
            '0001128024865',
            '0600485083',
            '+6287884042777',
            "O'Connor",
            "D'Angelo",
            "L'Equipe",
            '=HYPERLINK("https://evil.test","click")',
            '=IMPORTXML("https://evil.test", "//x")',
            '=SUM(A1:A2)',
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

    public function test_append_payload_uses_raw_mode_and_preserves_business_values(): void
    {
        $captured = [];
        $valuesResource = new class($captured) {
            public array $captured;

            public function __construct(array &$captured)
            {
                $this->captured = &$captured;
            }

            public function append(
                string $spreadsheetId,
                string $range,
                \Google\Service\Sheets\ValueRange $body,
                array $params = []
            ): void {
                $this->captured = [
                    'spreadsheetId' => $spreadsheetId,
                    'range' => $range,
                    'values' => $body->getValues(),
                    'params' => $params,
                ];
            }
        };

        $sheetsClient = new class($valuesResource) extends \Google\Service\Sheets {
            public $spreadsheets_values;

            public function __construct(object $valuesResource)
            {
                parent::__construct([]);
                $this->spreadsheets_values = $valuesResource;
            }
        };

        $factory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $factory->shouldReceive('getSheetsService')->once()->andReturn($sheetsClient);
        config(['google.spreadsheet_id' => 'test-spreadsheet']);

        $service = new GoogleSheetsService($factory);
        $this->assertTrue($service->appendRow('Employee', [
            '0003051435148',
            '+6287884042777',
            "O'Connor",
            '=SUM(A1:A2)',
        ]));

        $this->assertSame([
            'spreadsheetId' => 'test-spreadsheet',
            'range' => 'Employee!A:A',
            'values' => [[
                '0003051435148',
                '+6287884042777',
                "O'Connor",
                '=SUM(A1:A2)',
            ]],
            'params' => ['valueInputOption' => 'RAW'],
        ], $captured);
    }
}
