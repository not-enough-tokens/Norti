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
     * A2UI contract gap 12: `GetLearningProgress` calculaba estos conteos
     * dentro de la tool -- CLAUDE.md pide que las tools deleguen a un
     * Service, nunca contengan lógica financiera por sí mismas.
     *
     * @return array{total_topics: int, completed_topics: int, pending_topics: int, completion_percentage: int}
     */
    public function getProgressSummary(User $user): array
    {
        $learningPath = $this->getLearningPath($user);

        $total = $learningPath->count();
        $completed = $learningPath->where('is_completed', true)->count();

        return [
            'total_topics' => $total,
            'completed_topics' => $completed,
            'pending_topics' => $total - $completed,
            'completion_percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * A2UI contract gap 11: `get_educational_topic` no traía esta señal, así
     * que el componente no podía ocultar «Marcar como completado» para un
     * tema que el usuario ya completó.
     */
    public function isTopicCompleted(User $user, int $topicId): bool
    {
        return $user->educationalTopics()
            ->wherePivotNotNull('completed_at')
            ->where('educational_topics.id', $topicId)
            ->exists();
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
