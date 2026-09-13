<?php

namespace App\Http\Controllers;

use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\Onboarding\OnboardingIntakeParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Punto de entrada post-login/registro. Un chat de preguntas fijas (sin LLM,
 * sin M5) que recolecta ingreso, monto a invertir, horizonte y tolerancia al
 * riesgo, y los guarda en FinancialProfile. La conversación vive en la
 * sesión -- no hay tabla de mensajes, es un wizard, no un historial real.
 */
class OnboardingController extends Controller
{
    private const SESSION_KEY = 'onboarding_chat';

    /**
     * Orden de las preguntas fijas y a qué responde cada una. `risk` se
     * guarda tal cual en `risk_tolerance` (string libre, ver CLAUDE.md) --
     * RiskAnalysisService::suggestRiskProfile() la normaliza después.
     */
    private const QUESTIONS = [
        'monthly_income' => '¿Cuánto es lo que ganas al mes?',
        'invest_amount' => '¿Cuánto planeas invertir?',
        'horizon' => '¿Cuál es tu meta en tiempo (1 año, 2 años, etc.)?',
        'risk' => '¿Qué tan arriesgado quieres que sea?',
    ];

    public function __construct(private readonly OnboardingIntakeParser $parser) {}

    public function index(Request $request): View
    {
        $state = $this->state($request);

        return view('onboarding.index', [
            'user' => $request->user(),
            'messages' => $state['messages'],
            'isDone' => $state['step'] === 'done',
        ]);
    }

    public function chat(Request $request): RedirectResponse
    {
        $state = $this->state($request);

        if ($state['step'] === 'done') {
            return redirect()->route('onboarding.index');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $message = trim($validated['message']);
        $state['messages'][] = ['role' => 'user', 'text' => $message];

        $questionKeys = array_keys(self::QUESTIONS);

        if ($state['step'] === 'intro') {
            $state['messages'][] = ['role' => 'assistant', 'text' => 'Ok, entiendo. Claro, mucho gusto en ayudarte.'];
            $state['step'] = $questionKeys[0];
            $state['messages'][] = ['role' => 'assistant', 'text' => self::QUESTIONS[$state['step']]];
        } else {
            $state['answers'][$state['step']] = $message;
            $currentIndex = array_search($state['step'], $questionKeys, true);
            $nextKey = $questionKeys[$currentIndex + 1] ?? null;

            if ($nextKey !== null) {
                $state['step'] = $nextKey;
                $state['messages'][] = ['role' => 'assistant', 'text' => self::QUESTIONS[$nextKey]];
            } else {
                $profile = $this->saveProfile($request->user(), $state['answers']);
                $state['step'] = 'done';
                $state['messages'][] = ['role' => 'assistant', 'text' => $this->summaryMessage($profile)];
            }
        }

        $request->session()->put(self::SESSION_KEY, $state);

        return redirect()->route('onboarding.index');
    }

    /**
     * @return array{messages: array<int, array{role: string, text: string}>, step: string, answers: array<string, string>}
     */
    private function state(Request $request): array
    {
        $session = $request->session();

        if (! $session->has(self::SESSION_KEY)) {
            $session->put(self::SESSION_KEY, [
                'messages' => [
                    ['role' => 'assistant', 'text' => "Hola {$request->user()->name}, soy Norti, ¿en qué puedo ayudarte hoy?"],
                ],
                'step' => 'intro',
                'answers' => [],
            ]);
        }

        return $session->get(self::SESSION_KEY);
    }

    /**
     * @param  array<string, string>  $answers  Texto crudo por pregunta.
     */
    private function saveProfile(User $user, array $answers): FinancialProfile
    {
        $income = $this->parser->parseAmount($answers['monthly_income'] ?? '');
        $investAmount = $this->parser->parseAmount($answers['invest_amount'] ?? '');
        $horizonMonths = $this->parser->parseHorizonMonths($answers['horizon'] ?? '');

        return FinancialProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_filter([
                'monthly_income' => $income,
                'savings' => $investAmount,
                'investment_horizon_months' => $horizonMonths,
                'risk_tolerance' => trim($answers['risk'] ?? '') ?: null,
            ], fn ($value) => $value !== null)
        );
    }

    private function summaryMessage(FinancialProfile $profile): string
    {
        $income = number_format((float) $profile->monthly_income, 0);
        $invest = number_format((float) $profile->savings, 0);
        $years = round($profile->investment_horizon_months / 12, 1);

        return "¡Listo! Con esto arranco tu perfil: ganas \${$income} al mes, planeas invertir \${$invest}, ".
            "tu meta es a {$years} años y tu perfil de riesgo es \"{$profile->risk_tolerance}\". ".
            'Puedes continuar y ajustarlo después.';
    }
}
