<?php

use App\Http\Controllers\EducationalTopicController;
use App\Models\Asset;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/education', [EducationalTopicController::class, 'index'])
    ->name('education.index');

Route::get('/education/{educationalTopic}', [EducationalTopicController::class, 'show'])
    ->name('education.show');

if (app()->environment('local')) {
    Route::get('/mcp-test', fn () => view('debug.mcp-tester'))->name('mcp.debug-tester');

    Route::get('/mcp-test/seed', function () {
        $user = User::firstOrCreate(
            ['email' => 'demo@banorte.local'],
            ['name' => 'Demo User', 'password' => bcrypt('password')]
        );

        FinancialProfile::firstOrCreate(['user_id' => $user->id], [
            'monthly_income' => 35000,
            'monthly_expenses' => 22000,
            'savings' => 80000,
            'risk_tolerance' => 'moderate',
            'investment_horizon_months' => 60,
        ]);

        $portfolio = Portfolio::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Portafolio Demo'],
            ['description' => 'Sembrado por /mcp-test/seed']
        );

        $assets = [
            'AAPL' => ['name' => 'Apple Inc.', 'asset_type' => 'accion', 'currency' => 'USD', 'quantity' => 10, 'average_cost' => 180],
            'MSFT' => ['name' => 'Microsoft Corp.', 'asset_type' => 'accion', 'currency' => 'USD', 'quantity' => 5, 'average_cost' => 300],
            'CETES28' => ['name' => 'CETES 28 días', 'asset_type' => 'bono', 'currency' => 'MXN', 'quantity' => 1000, 'average_cost' => 10],
        ];

        foreach ($assets as $symbol => $data) {
            $asset = Asset::firstOrCreate(
                ['symbol' => $symbol],
                ['name' => $data['name'], 'asset_type' => $data['asset_type'], 'currency' => $data['currency']]
            );

            Holding::firstOrCreate(
                ['portfolio_id' => $portfolio->id, 'asset_id' => $asset->id],
                ['quantity' => $data['quantity'], 'average_cost' => $data['average_cost']]
            );
        }

        $token = $user->createToken('mcp-test-debug', ['mcp:read', 'mcp:simulate'])->accessToken;

        return response()->json([
            'token' => $token,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    })->name('mcp.debug-seed');
}
