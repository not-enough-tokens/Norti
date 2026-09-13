<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los /mcp-test/* son herramientas de desarrollo (seed rápido, tester manual,
 * visor de audit logs) -- nunca deben responder fuera de local/testing.
 *
 * Antes esto se resolvía envolviendo las rutas en
 * `if (app()->environment(['local', 'testing']))` en routes/web.php, lo cual
 * no es testeable: las rutas se registran una sola vez al boot, así que
 * cambiar el entorno a mitad de un test no las hace desaparecer. Como
 * middleware, el chequeo corre en cada request y sí se puede probar.
 */
class EnsureDebugRoutesAreAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return $next($request);
    }
}
