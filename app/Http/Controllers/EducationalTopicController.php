<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Models\User;
use App\Services\FinancialEducationIntegrationService;
use App\Services\FinancialEducationService;

class EducationalTopicController extends Controller
{
    public function index(
        FinancialEducationService $service,
        FinancialEducationIntegrationService $integrationService
    ) {
        $user = User::first();

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
