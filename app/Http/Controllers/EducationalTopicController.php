<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
<<<<<<< HEAD
use App\Services\FinancialEducationIntegrationService;
use App\Services\FinancialEducationService;
use Illuminate\Http\Request;
=======
use App\Models\User;
use App\Services\FinancialEducationIntegrationService;
use App\Services\FinancialEducationService;
>>>>>>> feature/education-mcp

class EducationalTopicController extends Controller
{
    public function index(
        Request $request,
        FinancialEducationService $service,
        FinancialEducationIntegrationService $integrationService
    ) {
<<<<<<< HEAD
        $user = $request->user();
=======
        $user = User::first();
>>>>>>> feature/education-mcp

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
