<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Services\FinancialEducationIntegrationService;
use App\Services\FinancialEducationService;
use Illuminate\Http\Request;

class EducationalTopicController extends Controller
{
    public function index(
        Request $request,
        FinancialEducationService $service,
        FinancialEducationIntegrationService $integrationService
    ) {
        $user = $request->user();

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
