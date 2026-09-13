<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
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

        $validated = $this->validateOrLog($request, [
            'amount' => ['required', 'numeric', 'gt:0'],
            'months' => ['required', 'integer', 'min:1', 'max:600'],
            'risk_profile' => ['required', 'string', 'in:conservative,moderate,aggressive'],
        ]);

        $amount = (float) $validated['amount'];
        $months = (int) $validated['months'];
        $riskProfile = $validated['risk_profile'];

        $result = $this->simulation->simulate($amount, $months, $riskProfile);
        $result['actions'] = $this->buildActions($amount, $months, $riskProfile);

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
     * Option Chip (plazo) y Option Row (perfil) del contrato A2UI: cada
     * alternativa re-ejecuta esta misma tool con un parámetro cambiado,
     * nunca recalcula nada del lado del componente.
     *
     * @return list<array<string, mixed>>
     */
    private function buildActions(float $amount, int $months, string $riskProfile): array
    {
        $actions = [];

        foreach ([12, 36, 60] as $alternateMonths) {
            if ($alternateMonths === $months) {
                continue;
            }

            $actions[] = ToolAction::make(
                "resimulate_{$alternateMonths}_months",
                "Simular a {$alternateMonths} meses",
                'simulate_investment',
                ['amount' => $amount, 'months' => $alternateMonths, 'risk_profile' => $riskProfile],
            );
        }

        $profileLabels = [
            'conservative' => 'Perfil conservador',
            'moderate' => 'Perfil moderado',
            'aggressive' => 'Perfil agresivo',
        ];

        foreach ($profileLabels as $alternateProfile => $label) {
            if ($alternateProfile === $riskProfile) {
                continue;
            }

            $actions[] = ToolAction::make(
                "resimulate_{$alternateProfile}",
                $label,
                'simulate_investment',
                ['amount' => $amount, 'months' => $months, 'risk_profile' => $alternateProfile],
            );
        }

        return $actions;
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
                ->description('Plazo de la simulación, en meses (máximo 600, 50 años).')
                ->required(),
            'risk_profile' => $schema->string()
                ->enum(['conservative', 'moderate', 'aggressive'])
                ->description('Perfil de riesgo a usar para la tasa asumida.')
                ->required(),
        ];
    }
}
