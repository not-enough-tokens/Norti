<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Services\Contracts\InvestmentSimulationServiceContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('simulate_investment')]
#[Description('Simula la proyección de una inversión dado un monto inicial, un plazo en meses y un perfil de riesgo. Es una proyección aritmética simplificada, no asesoría financiera.')]
class SimulateInvestment extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly InvestmentSimulationServiceContract $simulation,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:simulate')) {
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:simulate.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'months' => ['required', 'integer', 'min:1'],
            'risk_profile' => ['required', 'string', 'in:conservative,moderate,aggressive'],
        ]);

        $result = $this->simulation->simulate(
            (float) $validated['amount'],
            (int) $validated['months'],
            $validated['risk_profile'],
        );

        // Never log "amount" -- CLAUDE.md's audit policy forbids exact amounts in audit_logs.
        $this->logToolCall($request, success: true, safeInput: [
            'months' => $validated['months'],
            'risk_profile' => $validated['risk_profile'],
        ]);

        return Response::structured([
            'component' => 'simulation_result_card',
            'props' => $result,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()
                ->min(0.01)
                ->description('Monto inicial a invertir.')
                ->required(),
            'months' => $schema->integer()
                ->min(1)
                ->description('Plazo de la simulación, en meses.')
                ->required(),
            'risk_profile' => $schema->string()
                ->enum(['conservative', 'moderate', 'aggressive'])
                ->description('Perfil de riesgo a usar para la tasa asumida.')
                ->required(),
        ];
    }
}
