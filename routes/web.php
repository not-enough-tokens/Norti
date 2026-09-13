<?php

use App\Ai\Agents\BanorteMcpAgent;
use App\Ai\ToolInvocationCollector;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ChatController;
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
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Client;

// Landing pública (marca + CTAs a login/registro). Quien ya tiene sesión no
// necesita verla -- lo mandamos directo a onboarding.
Route::get('/', fn () => auth()->check() ? redirect()->route('onboarding.index') : view('home'))
    ->name('home');

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

Route::post('/onboarding/restart', [OnboardingController::class, 'restart'])
    ->middleware('auth')
    ->name('onboarding.restart');

Route::get('/education', [EducationalTopicController::class, 'index'])
    ->middleware('auth')
    ->name('education.index');

Route::get('/education/{educationalTopic}', [EducationalTopicController::class, 'show'])
    ->middleware('auth')
    ->name('education.show');

Route::post('/education/{educationalTopic}/complete', [EducationalTopicController::class, 'complete'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('education.complete');

Route::post('/mcp/token', [McpTokenController::class, 'store'])
    ->middleware('auth')
    ->name('mcp.token.issue');

// Chat real (M5 + A2UI): a diferencia de /onboarding (preguntas fijas, sin
// LLM), cada turno aquí invoca a BanorteMcpAgent contra las 11 tools reales.
Route::get('/chat', [ChatController::class, 'index'])
    ->middleware('auth')
    ->name('chat.index');

Route::post('/chat', [ChatController::class, 'send'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('chat.send');

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

    // Prototype for "can the chat render A2UI components" (see
    // docs/architecture/a2ui-components.md) -- runs a real agent prompt and
    // shows both its natural-language answer and the structured
    // component/props every tool call produced along the way, resolved to an
    // actual Blade component when one exists.
    Route::get('/mcp-test/chat', fn () => view('debug.mcp-chat-prototype', [
        'question' => null, 'email' => 'demo@banorte.local', 'answer' => null, 'invocations' => [],
    ]))->name('mcp.debug-chat');

    Route::post('/mcp-test/chat', function (HttpRequest $request, ToolInvocationCollector $collector) {
        $question = (string) $request->string('question');
        $email = (string) $request->string('email')->trim() ?: 'demo@banorte.local';

        $user = User::where('email', $email)->firstOrFail();
        $token = $user->createToken('mcp-chat-prototype', ['mcp:read', 'mcp:simulate', 'mcp:write'])->accessToken;

        // A real agent turn is several MCP + LLM round-trips (initialize,
        // tools/list, one tools/call per tool it decides to invoke, plus an
        // OpenAI completion between each) -- easily over PHP's 30s default
        // for web requests. mcp:demo-agent never hits this because the CLI
        // SAPI has no such cap.
        set_time_limit(120);

        $collector->reset();

        // Not url('/mcp/banorte'): inside a real HTTP request (unlike the
        // mcp:demo-agent CLI command) that helper resolves relative to the
        // CURRENT request's host, so hitting this debug route makes the
        // agent call back into the same single-threaded `php artisan serve`
        // process that's already busy serving this request -- a self-deadlock
        // that only times out after 30s. config('app.url') is a plain static
        // read, immune to that.
        $mcpUrl = rtrim((string) config('app.url'), '/').'/mcp/banorte';
        $client = Client::web($mcpUrl)->withToken($token)->connect();

        try {
            $answer = (string) (new BanorteMcpAgent($client))->prompt($question);
        } finally {
            $client->disconnect();
        }

        return view('debug.mcp-chat-prototype', [
            'question' => $question,
            'email' => $email,
            'answer' => $answer,
            'invocations' => $collector->all(),
        ]);
    })->name('mcp.debug-chat.submit');
});
