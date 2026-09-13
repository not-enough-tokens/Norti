<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Services\FinancialEducationService;
use App\Services\FinancialEducationIntegrationService;

class EducationalTopicController extends Controller
{
    public function index(
        FinancialEducationService $service,
        FinancialEducationIntegrationService $integrationService
    ) {
        $user = \App\Models\User::first();

        $learningPath = $service->getLearningPath($user);

        $financialContext = $integrationService
            ->getFinancialContext($user);

        return view('education.index', [
            'learningPath' => $learningPath,
            'financialContext' => $financialContext,
        ]);
    }

    public function show(EducationalTopic $educationalTopic)
    {
        return view('education.show', [
            'topic' => $educationalTopic,
        ]);
    }
}
