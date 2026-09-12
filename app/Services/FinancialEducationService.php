<?php

namespace App\Services;

use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Support\Collection;

class FinancialEducationService
{
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
}
