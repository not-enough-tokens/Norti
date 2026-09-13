<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AnalyzePortfolio;
use App\Mcp\Tools\GetAssetInformation;
use App\Mcp\Tools\GetFinancialProfile;
use App\Mcp\Tools\GetMarketSnapshot;
use App\Mcp\Tools\GetPortfolio;
use App\Mcp\Tools\SimulateInvestment;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Banorte Server')]
#[Version('0.0.1')]
#[Instructions('Capacidades financieras de Banorte: perfil financiero, portafolio, análisis de riesgo, información de activos, cotizaciones de mercado y simulación de inversión. Todas las tools de lectura requieren el scope mcp:read; simulate_investment requiere mcp:simulate. get_financial_profile solo debe pedirse con detail=exact si el usuario lo solicitó explícitamente.')]
class BanorteServer extends Server
{
    protected array $tools = [
        GetFinancialProfile::class,
        GetPortfolio::class,
        AnalyzePortfolio::class,
        GetAssetInformation::class,
        GetMarketSnapshot::class,
        SimulateInvestment::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
