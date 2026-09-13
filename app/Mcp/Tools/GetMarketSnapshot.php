<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ChartData;
use App\Mcp\Support\ToolAction;
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
    use LogsToolInvocation;

    public function __construct(
        private readonly MarketDataProviderContract $marketData,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            return $this->errorResponse($request, 'scope_denied', 'No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $this->validateOrLog($request, [
            'symbols' => ['required', 'array', 'min:1', 'max:10'],
            'symbols.*' => ['string', 'regex:/^[A-Za-z0-9.\-]{1,15}$/'],
        ]);

        // array_unique: el mismo símbolo repetido gastaba una llamada por
        // repetición contra el límite de 8 req/min del plan gratuito.
        $symbols = array_values(array_unique(array_map(strtoupper(...), $validated['symbols'])));

        $quotes = [];
        $failed = 0;

        foreach ($symbols as $symbol) {
            try {
                $quotes[$symbol] = $this->marketData->quote($symbol);
                $quotes[$symbol]['actions'] = [
                    ToolAction::make(
                        "view_asset_{$symbol}",
                        "Ver información de {$symbol}",
                        'get_asset_information',
                        ['symbol' => $symbol],
                    ),
                ];
            } catch (MarketDataUnavailableException $exception) {
                $quotes[$symbol] = [
                    'error' => $exception->getMessage(),
                    'actions' => [
                        ToolAction::make(
                            "retry_{$symbol}",
                            'Reintentar',
                            'get_market_snapshot',
                            ['symbols' => [$symbol]],
                        ),
                    ],
                ];
                $failed++;
            }
        }

        // Los errores por símbolo se devuelven en el payload en vez de abortar,
        // pero el audit log decía 'ok' aunque no se hubiera obtenido una sola
        // cotización. Registrar lo que realmente pasó.
        $this->logToolCall(
            $request,
            success: $failed < count($symbols),
            safeInput: ['symbols' => $symbols, 'failed_count' => $failed],
            resultSummary: match (true) {
                $failed === 0 => 'ok',
                $failed === count($symbols) => 'market_data_unavailable',
                default => 'partial_market_data',
            },
        );

        return Response::structured([
            'component' => 'market_snapshot_grid',
            'props' => ['quotes' => $quotes, 'chart' => $this->changeChart($quotes)],
        ]);
    }

    /**
     * Columnas de `% change` del día (A2UI contract gap 16) -- `null` para un
     * símbolo cuya cotización falló o que no trajo `percent_change`.
     *
     * @param  array<string, array<string, mixed>>  $quotes
     */
    private function changeChart(array $quotes): ?array
    {
        if ($quotes === []) {
            return null;
        }

        $data = [];
        foreach ($quotes as $symbol => $quote) {
            $data[] = ['key' => $symbol, 'value' => $quote['percent_change'] ?? null];
        }

        $values = array_filter(array_column($data, 'value'), fn (?float $value): bool => $value !== null);

        if ($values === []) {
            return ChartData::make(type: 'column', data: $data, unit: 'percent');
        }

        // Dominio divergente y simétrico: un +1.2% y un -0.8% deben leerse a
        // la misma escala visual desde el 0, no cada uno con su propio rango.
        $bound = max(1.0, max(array_map('abs', $values)));

        return ChartData::make(
            type: 'column',
            data: $data,
            unit: 'percent',
            yAxis: ['domain' => [-$bound, $bound], 'ticks' => [-$bound, 0, $bound]],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'symbols' => $schema->array()
                ->items($schema->string())
                ->description('Lista de símbolos/tickers a consultar (máximo 10), ej. ["AAPL", "IPC", "CETES28"].')
                ->required(),
        ];
    }
}
