<?php

namespace App\Http\Controllers;

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
        $user = $request->user();

        $user->tokens()->where('name', 'mcp-session')->delete();

        $token = $user->createToken('mcp-session', ['mcp:read', 'mcp:simulate']);

        return response()->json([
            'token' => $token->accessToken,
            'expires_at' => $token->token->expires_at,
        ]);
    }
}
