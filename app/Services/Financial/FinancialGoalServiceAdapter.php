<?php

namespace App\Services\Financial;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\Contracts\FinancialGoalServiceContract;
use App\Services\InvestmentSimulationService;

/**
 * Adapter over Integrante A/M2's InvestmentSimulationService::projectForGoal()
 * / evaluateGoal() -- both already built, corrected, and tested, but never
 * called from anywhere until this tool (ADR 006). Respects the same
 * summary/exact split as EloquentFinancialProfileService: target_amount,
 * current_amount, and shortfall are exact amounts and only ever appear when
 * detail=exact.
 */
class FinancialGoalServiceAdapter implements FinancialGoalServiceContract
{
    public function __construct(
        private readonly InvestmentSimulationService $simulation,
    ) {}

    public function getGoals(User $user, string $detail = 'summary'): array
    {
        $profile = $user->financialProfile;

        return [
            'detail' => $detail,
            'goals' => $user->financialGoals
                ->map(fn (FinancialGoal $goal) => $this->goalToArray($goal, $profile, $detail))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function goalToArray(FinancialGoal $goal, ?FinancialProfile $profile, string $detail): array
    {
        $targetAmount = (float) $goal->target_amount;
        $currentAmount = (float) $goal->current_amount;

        $data = [
            'name' => $goal->name,
            'goal_type' => $goal->goal_type,
            'progress_percentage' => $targetAmount > 0
                ? round(($currentAmount / $targetAmount) * 100, 1)
                : 0.0,
        ];

        // Sin un FinancialProfile no hay tasa de rendimiento ni capacidad de
        // ahorro que proyectar -- se regresa el progreso nada más.
        if ($profile) {
            $simulation = $this->simulation->projectForGoal($goal, $profile);
            $evaluation = $this->simulation->evaluateGoal($goal, $simulation);

            $data['months_remaining'] = $simulation['months'];
            $data['is_overdue'] = $evaluation['is_overdue'];
            $data['reaches_goal'] = $evaluation['reaches_goal'];
        }

        if ($detail === 'exact') {
            $data['target_amount'] = $targetAmount;
            $data['current_amount'] = $currentAmount;
            $data['target_day'] = $goal->target_day->toDateString();

            if (isset($evaluation)) {
                $data['shortfall'] = $evaluation['shortfall'];
            }
        }

        return $data;
    }
}
