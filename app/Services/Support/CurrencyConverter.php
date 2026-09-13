<?php

namespace App\Services\Support;

/**
 * Normaliza montos en distintas monedas a una sola moneda base antes de
 * sumarlos (A2UI contract gap 8) -- el seed mezcla AAPL/MSFT en USD con
 * CETES28 en MXN dentro del mismo portafolio, y sumarlos crudo da un total
 * sin sentido.
 */
class CurrencyConverter
{
    public function baseCurrency(): string
    {
        return config('currency.base_currency', 'MXN');
    }

    /**
     * Una moneda sin tipo de cambio configurado se convierte 1:1 en vez de
     * tronar la tool -- es un dato de calidad cuestionable (holding con una
     * moneda que no está en el catálogo), no un error del usuario.
     */
    public function toBaseCurrency(float $amount, string $currency): float
    {
        $rate = config("currency.exchange_rates.{$currency}", 1.0);

        return $amount * $rate;
    }
}
