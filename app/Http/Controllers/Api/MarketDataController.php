<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketData\ProfileRequest;
use App\Http\Requests\MarketData\QuoteRequest;
use App\Http\Requests\MarketData\TimeSeriesRequest;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Http\JsonResponse;

class MarketDataController extends Controller
{
    public function __construct(protected readonly TwelveDataClient $twelveData) {}

    /**
     * GET /api/market-data/quote
     */
    public function quote(QuoteRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->respond(fn (): array => $this->twelveData->quote($data['symbol'], $data));
    }

    /**
     * GET /api/market-data/time-series
     */
    public function timeSeries(TimeSeriesRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->respond(fn (): array => $this->twelveData->timeSeries($data['symbol'], $data['interval'], $data));
    }

    /**
     * GET /api/market-data/profile
     */
    public function profile(ProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->respond(fn (): array => $this->twelveData->profile($data['symbol'], $data));
    }

    /**
     * Execute a TwelveData call and translate any failure into a JSON error response.
     */
    protected function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (TwelveDataException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->statusCode());
        }
    }
}
