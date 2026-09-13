<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
use App\Services\FinancialEducationService;
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
        return view('education.index', [
            'learningPath' => $service->getLearningPath($request->user()),
        ]);
    }

    public function show(EducationalTopic $educationalTopic)
    {
        return view('education.show', [
            'topic' => $educationalTopic,
        ]);
    }
}
