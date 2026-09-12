<?php

namespace App\Providers;

use App\Services\Contracts\FinancialProfileServiceContract;
use App\Services\Contracts\InvestmentSimulationServiceContract;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;
use App\Services\Financial\EloquentFinancialProfileService;
use App\Services\Financial\EloquentPortfolioService;
use App\Services\Financial\PlaceholderInvestmentSimulationService;
use App\Services\Financial\PlaceholderRiskAnalysisService;
use App\Services\MarketData\TwelveDataMarketDataProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings for the M3 Service Contracts. Swap the right-hand class here when
 * Integrante A/M2 delivers the real Risk/Simulation algorithms — no MCP tool
 * should need to change.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MarketDataProviderContract::class, TwelveDataMarketDataProvider::class);
        $this->app->bind(FinancialProfileServiceContract::class, EloquentFinancialProfileService::class);
        $this->app->bind(PortfolioServiceContract::class, EloquentPortfolioService::class);
        $this->app->bind(RiskAnalysisServiceContract::class, PlaceholderRiskAnalysisService::class);
        $this->app->bind(InvestmentSimulationServiceContract::class, PlaceholderInvestmentSimulationService::class);
    }
}
