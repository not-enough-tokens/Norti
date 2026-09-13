<?php

namespace Tests\Unit\Services\Onboarding;

use App\Services\Onboarding\OnboardingIntakeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingIntakeParserTest extends TestCase
{
    private OnboardingIntakeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new OnboardingIntakeParser;
    }

    /**
     * @return array<string, array{0: string, 1: float}>
     */
    public static function amountExamples(): array
    {
        return [
            'plain with thousands separator' => ['50,000 pesos', 50000.0],
            'bare number' => ['15000', 15000.0],
            'mil suffix' => ['20 mil', 20000.0],
            'k suffix' => ['10k', 10000.0],
            'millón' => ['1.5 millones', 1500000.0],
            'currency symbol' => ['$8,000 MXN', 8000.0],
        ];
    }

    #[DataProvider('amountExamples')]
    public function test_parses_amounts(string $text, float $expected): void
    {
        $this->assertSame($expected, $this->parser->parseAmount($text));
    }

    public function test_returns_null_when_no_number_is_present(): void
    {
        $this->assertNull($this->parser->parseAmount('no sé, poquito'));
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function horizonExamples(): array
    {
        return [
            'years' => ['2 años', 24],
            'months' => ['18 meses', 18],
            'bare number assumes years' => ['3', 36],
            'spelled out number' => ['un año', 12],
        ];
    }

    #[DataProvider('horizonExamples')]
    public function test_parses_horizon_in_months(string $text, int $expected): void
    {
        $this->assertSame($expected, $this->parser->parseHorizonMonths($text));
    }

    public function test_returns_null_when_no_horizon_is_found(): void
    {
        $this->assertNull($this->parser->parseHorizonMonths('no tengo idea'));
    }
}
