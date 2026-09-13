<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketData\ProfileRequest;
use App\Http\Requests\MarketData\QuoteRequest;
use App\Http\Requests\MarketData\TimeSeriesRequest;
use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class MarketDataController extends Controller
{
    public function __construct(protected readonly MarketDataProviderContract $marketData) {}

    /**
     * GET /api/market-data/quote
     */
    public function quote(QuoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $symbol = Arr::pull($data, 'symbol');

        return $this->respond(fn (): array => $this->marketData->quote($symbol, $data));
    }

    /**
     * GET /api/market-data/time-series
     */
    public function timeSeries(TimeSeriesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $symbol = Arr::pull($data, 'symbol');
        $interval = Arr::pull($data, 'interval');

        return $this->respond(fn (): array => $this->marketData->timeSeries($symbol, $interval, $data));
    }

    /**
     * GET /api/market-data/profile
     */
    public function profile(ProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $symbol = Arr::pull($data, 'symbol');

        return $this->respond(fn (): array => $this->marketData->profile($symbol, $data));
    }

    /**
     * Execute a market-data call and translate any failure into a JSON error response.
     */
    protected function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (MarketDataUnavailableException $exception) {
            $previous = $exception->getPrevious();
            $statusCode = $previous instanceof TwelveDataException ? $previous->statusCode() : 502;

            return response()->json(['message' => $exception->getMessage()], $statusCode);
        }
    }
}
