<?php

namespace Tests\Unit\Services;

use App\Models\FinancialProfile;
use App\Services\RiskAnalysisService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RiskAnalysisServiceTest extends TestCase
{
    private RiskAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RiskAnalysisService;
    }

    public function test_expected_annual_return_grows_with_risk(): void
    {
        $conservative = $this->service->expectedAnnualReturn('conservative');
        $moderate = $this->service->expectedAnnualReturn('moderate');
        $aggressive = $this->service->expectedAnnualReturn('aggressive');

        $this->assertLessThan($moderate, $conservative);
        $this->assertLessThan($aggressive, $moderate);
    }

    #[DataProvider('riskLevelProvider')]
    public function test_asset_allocation_sums_to_100_and_uses_db_vocabulary(string $level): void
    {
        $allocation = $this->service->assetAllocation($level);

        $this->assertSame(100, array_sum($allocation), "La distribución de '{$level}' no suma 100.");

        foreach (array_keys($allocation) as $assetType) {
            $this->assertContains(
                $assetType,
                ['accion', 'bono', 'fondo', 'efectivo'],
                "'{$assetType}' no es un asset_type válido de la tabla assets."
            );
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function riskLevelProvider(): array
    {
        return [['conservative'], ['moderate'], ['aggressive']];
    }

    public function test_risk_level_is_normalized_before_lookup(): void
    {
        $this->assertSame(
            $this->service->assetAllocation('moderate'),
            $this->service->assetAllocation('  Moderate ')
        );
    }

    public function test_unknown_risk_level_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->assetAllocation('temerario');
    }

    public function test_missing_config_entry_reports_the_missing_key(): void
    {
        config(['investment_rules.risk_levels' => ['conservative', 'moderate', 'aggressive', 'lunar']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lunar');

        $this->service->expectedAnnualReturn('lunar');
    }

    public function test_suggest_risk_profile_respects_a_stored_valid_value(): void
    {
        $profile = new FinancialProfile([
            'risk_tolerance' => 'aggressive',
            'investment_horizon_months' => 6, // apuntaría a conservative
        ]);

        $this->assertSame('aggressive', $this->service->suggestRiskProfile($profile));
    }

    public function test_suggest_risk_profile_normalizes_a_stored_value(): void
    {
        $profile = new FinancialProfile(['risk_tolerance' => 'Moderate', 'investment_horizon_months' => 6]);

        $this->assertSame('moderate', $this->service->suggestRiskProfile($profile));
    }

    /**
     * La columna risk_tolerance es un string libre: cualquier valor que el
     * onboarding escriba mal debe degradar al horizonte, no reventar.
     */
    #[DataProvider('unusableStoredValueProvider')]
    public function test_suggest_risk_profile_falls_back_to_the_horizon(?string $stored): void
    {
        $profile = new FinancialProfile([
            'risk_tolerance' => $stored,
            'investment_horizon_months' => 120,
        ]);

        $suggested = $this->service->suggestRiskProfile($profile);

        $this->assertSame('aggressive', $suggested);
        $this->assertNotEmpty($this->service->assetAllocation($suggested));
    }

    /**
     * @return array<string, array<int, string|null>>
     */
    public static function unusableStoredValueProvider(): array
    {
        return [
            'null' => [null],
            'vacío' => [''],
            'solo espacios' => ['   '],
            'valor desconocido' => ['muy agresivo'],
        ];
    }

    #[DataProvider('horizonProvider')]
    public function test_horizon_thresholds(int $months, string $expected): void
    {
        $profile = new FinancialProfile([
            'risk_tolerance' => null,
            'investment_horizon_months' => $months,
        ]);

        $this->assertSame($expected, $this->service->suggestRiskProfile($profile));
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public static function horizonProvider(): array
    {
        return [
            [0, 'conservative'],
            [23, 'conservative'],
            [24, 'moderate'],
            [59, 'moderate'],
            [60, 'aggressive'],
        ];
    }
}
