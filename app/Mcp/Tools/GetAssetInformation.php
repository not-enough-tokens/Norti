<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Models\Asset;
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

#[Name('get_asset_information')]
#[Description('Obtiene información de un activo por su símbolo: datos locales (tipo, moneda) y datos de mercado en vivo (perfil, última cotización) vía TwelveData.')]
class GetAssetInformation extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly MarketDataProviderContract $marketData,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $this->validateOrLog($request, [
            'symbol' => ['required', 'string', 'regex:/^[A-Za-z0-9.\-]{1,15}$/'],
        ]);

        // Los tickers del catálogo se guardan en mayúsculas y el `=` de Postgres
        // distingue mayúsculas: sin normalizar, 'aapl' no encontraba el Asset
        // 'AAPL' y la tool respondía local_asset => null para un símbolo que sí
        // está en el catálogo.
        $symbol = strtoupper($validated['symbol']);
        $asset = Asset::where('symbol', $symbol)->first();

        try {
            $quote = $this->marketData->quote($symbol);
            $profile = $this->marketData->profile($symbol);
        } catch (MarketDataUnavailableException $exception) {
            $this->logToolCall($request, success: false, safeInput: ['symbol' => $symbol], resultSummary: 'market_data_unavailable');

            return Response::error("No se pudo obtener información de mercado para {$symbol}: {$exception->getMessage()}");
        }

        $this->logToolCall($request, success: true, safeInput: ['symbol' => $symbol]);

        return Response::structured([
            'component' => 'asset_info_card',
            'props' => [
                'symbol' => $symbol,
                'local_asset' => $asset ? [
                    'name' => $asset->name,
                    'asset_type' => $asset->asset_type,
                    'currency' => $asset->currency,
                ] : null,
                'quote' => $quote,
                'profile' => $profile,
            ],
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'symbol' => $schema->string()
                ->description('Símbolo/ticker del activo, ej. "AAPL", "IPC".')
                ->required(),
        ];
    }
}
