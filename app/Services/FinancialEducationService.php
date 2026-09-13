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
                return $this->withRecommendationReason($topic, 'no_goals');
            }
        }

        if ($holdings->pluck('asset_id')->unique()->count() === 1) {
            $topic = $incomplete->firstWhere('slug', 'diversificacion');

            if ($topic) {
                return $this->withRecommendationReason($topic, 'concentrated_portfolio');
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
                return $this->withRecommendationReason($topic, 'conservative_profile_with_stocks');
            }
        }

        return $this->withRecommendationReason($incomplete->first(), 'default');
    }

    private function withRecommendationReason(EducationalTopic $topic, string $reason): EducationalTopic
    {
        $topic->recommended_reason = $reason;

        return $topic;
    }

    /**
     * Categorías donde el usuario no tiene ningún tema completado -- la
     * versión simple de "knowledge gaps": no es un algoritmo general, solo
     * agrupa la ruta de aprendizaje por categoría y reporta las que están en
     * cero.
     *
     * @return list<string>
     */
    public function getCategoryGaps(User $user): array
    {
        return $this->getLearningPath($user)
            ->groupBy('category')
            ->reject(fn (Collection $topics) => $topics->contains('is_completed', true))
            ->keys()
            ->values()
            ->all();
    }
}
