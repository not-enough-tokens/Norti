<?php

namespace App\Mcp\Tools;

use App\Services\MarketDataService;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get the latest market quote for a given ticker symbol.')]
class GetMarketQuote extends Tool
{
    public function __construct(private readonly MarketDataService $marketData) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:20'],
            'exchange' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:56'],
            'type' => ['sometimes', 'string', 'max:30'],
        ]);

        $symbol = Arr::pull($validated, 'symbol');

        try {
            $quote = $this->marketData->getQuote($symbol, $validated);
        } catch (TwelveDataException $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::text(json_encode($quote, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'symbol' => $schema->string()->description('Ticker symbol, e.g. AAPL.')->max(20)->required(),
            'exchange' => $schema->string()->description('Exchange filter, e.g. NASDAQ.')->max(50),
            'country' => $schema->string()->description('Country filter, e.g. United States.')->max(56),
            'type' => $schema->string()->description('Instrument type filter, e.g. Common Stock.')->max(30),
        ];
    }
}
