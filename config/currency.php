<?php

// Tipos de cambio sintéticos para poder sumar valores en monedas distintas
// dentro de un mismo portafolio (el seed mezcla AAPL/MSFT en USD con CETES28
// en MXN -- A2UI contract gap 8, docs/architecture/a2ui-components.md). No
// son tasas de mercado en vivo: son un supuesto de demo, igual que las tasas
// de investment_rules.php.

return [

    'base_currency' => 'MXN',

    // Unidades de base_currency por 1 unidad de la moneda.
    'exchange_rates' => [
        'MXN' => 1.0,
        'USD' => 18.5,
    ],

];
