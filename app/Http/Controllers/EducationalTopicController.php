<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Models\User;
use App\Services\FinancialEducationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EducationalTopicController extends Controller
{
    /**
     * Nota: aquí se construía también un $financialContext vía
     * FinancialEducationIntegrationService y se pasaba a la vista, que nunca lo
     * usó -- tres queries por carga cuyo resultado se descartaba, y un
     * FinancialProfile con montos exactos suelto en el scope de la vista.
     *
     * El servicio sigue existiendo: integrar el contexto financiero en la
     * sección de educación es intención real de M6. Cuando esa vista se
     * construya, se vuelve a inyectar aquí de forma deliberada.
     */
    public function index(Request $request, FinancialEducationService $service)
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return view('education.index', [
            'user' => $user,
            'learningPath' => $service->getLearningPath($user),
            'progress' => $service->getProgressSummary($user),
        ]);
    }

    public function show(Request $request, EducationalTopic $educationalTopic, FinancialEducationService $service)
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $recommended = $service->getRecommendedTopic($user);

        return view('education.show', [
            'user' => $user,
            'topic' => $educationalTopic,
            'isCompleted' => $service->isTopicCompleted($user, $educationalTopic->id),
            'recommendedReason' => $recommended?->id === $educationalTopic->id
                ? $recommended->recommended_reason
                : null,
        ]);
    }

    public function complete(Request $request, EducationalTopic $educationalTopic, FinancialEducationService $service): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $service->markCompleted($user, $educationalTopic);

        return redirect()->route('education.show', $educationalTopic)
            ->with('status', 'Tema marcado como completado');
    }
}
