<?php

namespace App\Providers;

use App\Services\Contracts\FinancialProfileServiceContract;
use App\Services\Contracts\InvestmentSimulationServiceContract;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;
use App\Services\Financial\EloquentFinancialProfileService;
use App\Services\Financial\EloquentPortfolioService;
use App\Services\Financial\InvestmentSimulationServiceAdapter;
use App\Services\Financial\RiskAnalysisServiceAdapter;
use App\Services\MarketData\TwelveDataMarketDataProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings for the M3 Service Contracts.
 *
 * RiskAnalysisServiceAdapter/InvestmentSimulationServiceAdapter wrap
 * Integrante A/M2's real RiskAnalysisService/InvestmentSimulationService.
 * MarketDataProviderContract still binds to our own TwelveDataMarketDataProvider
 * rather than Integrante C's TwelveDataProvider (app/Services/MarketData) --
 * that one's getHistoricalPrices()/getAssetProfile() are still stubs, so
 * switching would regress get_asset_information/get_market_snapshot.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MarketDataProviderContract::class, TwelveDataMarketDataProvider::class);
        $this->app->bind(FinancialProfileServiceContract::class, EloquentFinancialProfileService::class);
        $this->app->bind(PortfolioServiceContract::class, EloquentPortfolioService::class);
        $this->app->bind(RiskAnalysisServiceContract::class, RiskAnalysisServiceAdapter::class);
        $this->app->bind(InvestmentSimulationServiceContract::class, InvestmentSimulationServiceAdapter::class);
    }
}
