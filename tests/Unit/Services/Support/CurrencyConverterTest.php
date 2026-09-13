<?php

namespace Tests\Unit\Services\Support;

use App\Services\Support\CurrencyConverter;
use Tests\TestCase;

class CurrencyConverterTest extends TestCase
{
    public function test_converts_an_amount_using_the_configured_rate(): void
    {
        $converter = new CurrencyConverter;

        $this->assertSame(1850.0, $converter->toBaseCurrency(100.0, 'USD'));
    }

    public function test_the_base_currency_converts_one_to_one(): void
    {
        $converter = new CurrencyConverter;

        $this->assertSame(100.0, $converter->toBaseCurrency(100.0, 'MXN'));
        $this->assertSame('MXN', $converter->baseCurrency());
    }

    /**
     * Una moneda sin tipo de cambio configurado no debe tronar la tool -- es
     * un dato de calidad cuestionable, no un error del usuario.
     */
    public function test_an_unconfigured_currency_converts_one_to_one(): void
    {
        $converter = new CurrencyConverter;

        $this->assertSame(100.0, $converter->toBaseCurrency(100.0, 'EUR'));
    }
}
