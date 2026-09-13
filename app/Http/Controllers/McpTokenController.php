<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Emite el Personal Access Token que el agente de IA usa contra el MCP
 * server, atado al usuario de la sesión web actual -- reemplaza el flujo
 * manual de tinker para producción. Protegido por la sesión estándar de
 * Laravel (auth), no por Passport, per CLAUDE.md.
 */
class McpTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return response()->json($this->issue($request->user()));
    }

    /**
     * Extraído de store() para que otros puntos de entrada server-side (ver
     * ChatController) puedan emitir el mismo token sin un round-trip HTTP a
     * esta misma ruta.
     *
     * @return array{token: string, expires_at: ?Carbon}
     */
    public function issue(User $user): array
    {
        $user->tokens()->where('name', 'mcp-session')->delete();

        $token = $user->createToken('mcp-session', ['mcp:read', 'mcp:simulate', 'mcp:write']);

        return [
            'token' => $token->accessToken,
            'expires_at' => $token->token->expires_at,
        ];
    }
}
