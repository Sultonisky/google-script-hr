<?php

namespace Tests\Feature;

use App\Models\OutsourceIncentive;
use App\Models\OutsourcePayslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutsourcePayrollApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_TOKEN = 'test-outsource-payroll-integration-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.integration.outsource_payroll_api_token' => self::API_TOKEN]);
    }

    public function test_payslip_api_returns_only_requested_outsource_history_as_numeric_data(): void
    {
        OutsourcePayslip::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'hke' => 24.5,
            'basic_salary' => 3200000,
            'bpjs_kesehatan_deduction' => 32000,
            'loan_deduction' => 0,
            'take_home_pay' => 3168000,
        ]);
        OutsourcePayslip::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260002',
            'full_name' => 'Citra Lestari',
            'vendor' => 'StaffInc',
            'hke' => 26,
            'basic_salary' => 4000000,
            'bpjs_kesehatan_deduction' => 40000,
            'loan_deduction' => 0,
            'take_home_pay' => 3960000,
        ]);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/outsource/dm20260001/payslips')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('outsource_id', 'DM20260001')
            ->assertJsonPath('filters.period', null)
            ->assertJsonPath('data.0.period', '2026-09')
            ->assertJsonPath('data.0.hke', 24.5)
            ->assertJsonPath('data.0.loan_deduction', 0)
            ->assertJsonPath('data.0.take_home_pay', 3168000)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonMissing(['outsource_id' => 'DM20260002'])
            ->assertJsonMissingPath('data.0.source_file')
            ->assertJsonMissingPath('data.0.imported_by');
    }

    public function test_incentive_api_returns_numeric_amounts_and_can_filter_period(): void
    {
        OutsourceIncentive::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'umk_amount' => null,
            'incentive_amount' => 1250000,
        ]);
        OutsourceIncentive::query()->create([
            'period' => '2026-08',
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'umk_amount' => 4500000,
            'incentive_amount' => 1000000,
        ]);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/outsource/DM20260001/incentives?period=2026-09')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filters.period', '2026-09')
            ->assertJsonPath('data.0.umk_amount', 0)
            ->assertJsonPath('data.0.incentive_amount', 1250000)
            ->assertJsonPath('meta.count', 1);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/outsource/DM20260001/incentives')
            ->assertOk()
            ->assertJsonPath('data.0.period', '2026-09')
            ->assertJsonPath('data.1.period', '2026-08')
            ->assertJsonPath('meta.count', 2);
    }

    public function test_outsource_payroll_api_requires_integration_bearer_token(): void
    {
        $this->getJson('/api/v1/outsource/DM20260001/payslips')->assertUnauthorized();
        $this->getJson('/api/v1/outsource/DM20260001/incentives')->assertUnauthorized();
    }

    public function test_outsource_payroll_api_fails_closed_when_its_token_is_not_configured(): void
    {
        config(['hris.integration.outsource_payroll_api_token' => '']);

        $this->getJson('/api/v1/outsource/DM20260001/payslips')
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    public function test_outsource_payroll_api_validates_period_format(): void
    {
        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/outsource/DM20260001/payslips?period=2026-13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }
}
