<?php

use App\Http\Controllers\Api\MarketDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Estas rutas son un proxy directo a TwelveData (API de terceros, de paga y con
// cuota). Sin auth cualquiera en internet podía agotar los 8 req/min del plan y
// tumbar el market data de toda la app. `auth:api` = Passport, el mismo guard
// que /mcp/banorte, porque son rutas stateless sin sesión web.
Route::prefix('market-data')
    ->name('market-data.')
    ->middleware(['auth:api', 'throttle:market-data'])
    ->controller(MarketDataController::class)
    ->group(function (): void {
        Route::get('/quote', 'quote')->name('quote');
        Route::get('/time-series', 'timeSeries')->name('time-series');
        Route::get('/profile', 'profile')->name('profile');
    });
