<?php

namespace App\Services;

use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Support\Collection;

class FinancialEducationService
{
    public function __construct(
        private readonly FinancialEducationIntegrationService $integration,
        private readonly RiskAnalysisService $riskAnalysis,
    ) {}

    public function getLearningPath(User $user): Collection
    {
        $completedTopicIds = $user->educationalTopics()
            ->wherePivotNotNull('completed_at')
            ->pluck('educational_topics.id');

        return EducationalTopic::orderBy('id')
            ->get()
            ->map(function (EducationalTopic $topic) use ($completedTopicIds) {
                $topic->is_completed = $completedTopicIds->contains($topic->id);

                return $topic;
            });
    }

    /**
     * Prioriza, entre los temas sin completar, el más relevante a la
     * situación financiera actual del usuario. Solo usa señales derivadas
     * (si tiene metas, si el portafolio está diversificado, el perfil de
     * riesgo normalizado) -- nunca montos exactos del FinancialProfile, para
     * no repetir el leak que tenía la integración anterior (ver
     * EducationalTopicController).
     */
    public function getRecommendedTopic(User $user): ?EducationalTopic
    {
        $incomplete = $this->getLearningPath($user)->where('is_completed', false);

        if ($incomplete->isEmpty()) {
            return null;
        }

        $context = $this->integration->getFinancialContext($user);
        $holdings = $context['portfolios']->flatMap(fn ($portfolio) => $portfolio->holdings);

        if ($context['goals']->isEmpty()) {
            $topic = $incomplete->firstWhere('slug', 'ahorro-vs-inversion');

            if ($topic) {
                return $topic;
            }
        }

        if ($holdings->pluck('asset_id')->unique()->count() === 1) {
            $topic = $incomplete->firstWhere('slug', 'diversificacion');

            if ($topic) {
                return $topic;
            }
        }

        $riskProfile = $context['profile']
            ? $this->riskAnalysis->suggestRiskProfile($context['profile'])
            : null;

        $hasAggressiveHolding = $holdings->contains(
            fn ($holding) => $holding->asset?->asset_type === 'accion'
        );

        if ($riskProfile === 'conservative' && $hasAggressiveHolding) {
            $topic = $incomplete->firstWhere('slug', 'riesgo-de-inversion');

            if ($topic) {
                return $topic;
            }
        }

        return $incomplete->first();
    }
}
