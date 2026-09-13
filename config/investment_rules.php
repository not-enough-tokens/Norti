<?php

// Reglas de negocio para el motor financiero, calibradas con referencias
// reales de Banxico (CETES/TIIE) y el índice S&P/BMV IPC — ver investigación
// de mercado hecha para el hackathon. Esto NO son garantías de rendimiento,
// son supuestos de simulación.

return [

    // Rendimiento anual esperado (simulado) por perfil de riesgo.
    'expected_annual_return' => [
        'conservative' => 0.065, // 6.5%
        'moderate' => 0.09,  // 9.0%
        'aggressive' => 0.125, // 12.5%
    ],

    // Distribución sugerida (%) por tipo de activo, según perfil de riesgo.
    // Los valores de cada perfil deben sumar 100.
    //
    // Las llaves usan el mismo vocabulario que la columna `assets.asset_type`
    // (accion/bono/fondo/efectivo). Antes estaban en inglés (bond/fund/stock) y
    // había que traducirlas en cada consumidor -- PortfolioService::createForUser()
    // no traducía y por eso nunca encontraba un Asset que empatara.
    'asset_allocation' => [
        'conservative' => [
            'bono' => 80,
            'fondo' => 20,
        ],
        'moderate' => [
            'bono' => 50,
            'fondo' => 30,
            'accion' => 20,
        ],
        'aggressive' => [
            'bono' => 10,
            'fondo' => 30,
            'accion' => 60,
        ],
    ],

    // Perfiles de riesgo válidos, en orden de menor a mayor riesgo.
    'risk_levels' => ['conservative', 'moderate', 'aggressive'],
];
