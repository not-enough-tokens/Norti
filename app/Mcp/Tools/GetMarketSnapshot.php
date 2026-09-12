<?php

namespace App\Mcp\Tools;

use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_market_snapshot')]
#[Description('Obtiene una cotización rápida en vivo para una lista de símbolos/tickers, vía TwelveData.')]
class GetMarketSnapshot extends Tool
{
    public function __construct(
        private readonly MarketDataProviderContract $marketData,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $request->validate([
            'symbols' => ['required', 'array', 'min:1'],
            'symbols.*' => ['string'],
        ]);

        $quotes = [];

        foreach ($validated['symbols'] as $symbol) {
            try {
                $quotes[$symbol] = $this->marketData->quote($symbol);
            } catch (MarketDataUnavailableException $exception) {
                $quotes[$symbol] = ['error' => $exception->getMessage()];
            }
        }

        return Response::structured([
            'component' => 'market_snapshot_grid',
            'props' => ['quotes' => $quotes],
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'symbols' => $schema->array()
                ->items($schema->string())
                ->description('Lista de símbolos/tickers a consultar, ej. ["AAPL", "IPC", "CETES28"].')
                ->required(),
        ];
    }
}
