<?php

use App\Http\Controllers\Api\MarketDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('market-data')->name('market-data.')->controller(MarketDataController::class)->group(function (): void {
    Route::get('/quote', 'quote')->name('quote');
    Route::get('/time-series', 'timeSeries')->name('time-series');
    Route::get('/profile', 'profile')->name('profile');
});
