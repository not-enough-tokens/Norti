<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\EducationalTopicController;
use App\Http\Controllers\McpTokenController;
use App\Http\Controllers\OnboardingController;
use App\Http\Middleware\EnsureDebugRoutesAreAllowed;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Sin pantalla propia: manda siempre a /login, que a su vez redirige a
// onboarding si ya hay sesión (redirectUsersTo en bootstrap/app.php).
Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

// `login` (above) is also the fallback redirect target for Laravel's
// default auth middleware, so keep that route name even after this
// real form replaces the earlier placeholder.
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Post-login/register landing spot -- see OnboardingController for why
// this exists as a dedicated step instead of going straight to /education.
Route::get('/onboarding', [OnboardingController::class, 'index'])
    ->middleware('auth')
    ->name('onboarding.index');

Route::post('/onboarding', [OnboardingController::class, 'chat'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('onboarding.chat');

Route::get('/education', [EducationalTopicController::class, 'index'])
    ->middleware('auth')
    ->name('education.index');

Route::get('/education/{educationalTopic}', [EducationalTopicController::class, 'show'])
    ->middleware('auth')
    ->name('education.show');

Route::post('/mcp/token', [McpTokenController::class, 'store'])
    ->middleware('auth')
    ->name('mcp.token.issue');

Route::middleware(EnsureDebugRoutesAreAllowed::class)->group(function (): void {
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

        $token = $user->createToken('mcp-test-debug', ['mcp:read', 'mcp:simulate', 'mcp:write'])->accessToken;

        return response()->json([
            'token' => $token,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    })->name('mcp.debug-seed');

    Route::get('/mcp-test/audit-logs', fn () => AuditLog::latest()->limit(50)->get())
        ->name('mcp.debug-audit-logs');
});
