<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Models\User;
use App\Services\FinancialEducationService;

class EducationalTopicController extends Controller
{
    public function index(FinancialEducationService $service)
    {
        $user = User::first();

        $learningPath = $service->getLearningPath($user);

        return view('education.index', [
            'learningPath' => $learningPath,
        ]);
    }

    public function show(EducationalTopic $educationalTopic)
    {
        return view('education.show', [
            'topic' => $educationalTopic,
        ]);
    }
}
