<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Closure: route() no existe todavía mientras bootstrap/app.php se evalúa.
        $middleware->redirectUsersTo(fn () => route('onboarding.index'));

        // La app nunca recibe tráfico directo en producción (Laravel Cloud,
        // o cualquier PaaS) -- siempre pasa por el proxy/load balancer de la
        // plataforma. Sin confiar en él, Laravel cree que cada request es
        // http:// (rompe cookies "secure", CSRF y las URLs que genera).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
