<?php

namespace App\Http\Controllers;

use App\Models\EducationalTopic;
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
     * Orden de las preguntas fijas y a qué responde cada una.
     */
    private const QUESTIONS = [
        'monthly_income' => '¿Cuánto es lo que ganas al mes?',
        'invest_amount' => '¿Cuánto planeas invertir?',
        'horizon' => '¿Cuál es tu meta en tiempo (1 año, 2 años, etc.)?',
        'risk' => '¿Qué tan arriesgado quieres que sea?',
    ];

    /**
     * `risk` no es texto libre: son estas 3 opciones (Option Chip, nodo
     * 16:150), guardadas tal cual en `risk_tolerance` -- ya en el vocabulario
     * que espera RiskAnalysisService::normalizeRiskLevel(), sin depender de
     * que el parser adivine lo que la persona quiso decir.
     */
    public const RISK_OPTIONS = [
        'conservative' => 'Conservador',
        'moderate' => 'Moderado',
        'aggressive' => 'Agresivo',
    ];

    public function __construct(private readonly OnboardingIntakeParser $parser) {}

    public function index(Request $request): View
    {
        $topic = $request->filled('topic')
            ? EducationalTopic::where('slug', $request->string('topic'))->first()
            : null;

        $state = $this->state($request, $topic);

        return view('onboarding.index', [
            'user' => $request->user(),
            'messages' => $state['messages'],
            'step' => $state['step'],
            'isDone' => $state['step'] === 'done',
            'riskOptions' => self::RISK_OPTIONS,
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

        // El chip de riesgo manda el valor canónico ("moderate") como
        // `message` -- la burbuja del usuario muestra la etiqueta en español,
        // no ese valor interno.
        $displayText = $state['step'] === 'risk'
            ? (self::RISK_OPTIONS[$message] ?? $message)
            : $message;

        $state['messages'][] = ['role' => 'user', 'text' => $displayText];
        $state['answers'][$state['step']] = $message;

        $questionKeys = array_keys(self::QUESTIONS);
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

        $request->session()->put(self::SESSION_KEY, $state);

        return redirect()->route('onboarding.index');
    }

    /**
     * "Empezar un nuevo chat" (pantalla final): no repite las 4 preguntas si
     * ya existe un FinancialProfile -- ver seedState().
     */
    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('onboarding.index');
    }

    /**
     * @return array{messages: array<int, array{role: string, text: string}>, step: string, answers: array<string, string>}
     */
    private function state(Request $request, ?EducationalTopic $topic = null): array
    {
        $session = $request->session();
        $current = $session->get(self::SESSION_KEY);

        // Un `?topic=` llegando sobre una conversación ya cerrada abre una
        // conversación nueva con ese contexto; sobre una en curso se ignora
        // para no interrumpir las preguntas a medias.
        $needsSeed = $current === null || ($topic !== null && $current['step'] === 'done');

        if ($needsSeed) {
            $session->put(self::SESSION_KEY, $this->seedState($request->user(), $topic));
        }

        return $session->get(self::SESSION_KEY);
    }

    private function seedState(User $user, ?EducationalTopic $topic): array
    {
        $intro = "Hola {$user->name}, soy Norti.";

        if ($topic !== null) {
            $intro .= " Vi que estabas revisando \"{$topic->title}\" en Educación.";
        }

        if ($user->financialProfile()->exists()) {
            $closing = $topic !== null
                ? ' ¿Qué te gustaría saber sobre este tema?'
                : ' ¿En qué te puedo ayudar hoy?';

            return [
                'messages' => [['role' => 'assistant', 'text' => $intro.$closing]],
                'step' => 'done',
                'answers' => [],
            ];
        }

        $firstKey = array_key_first(self::QUESTIONS);

        return [
            'messages' => [
                ['role' => 'assistant', 'text' => $intro.' Para armar tu plan te voy a hacer unas preguntas rápidas.'],
                ['role' => 'assistant', 'text' => self::QUESTIONS[$firstKey]],
            ],
            'step' => $firstKey,
            'answers' => [],
        ];
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
        $risk = self::RISK_OPTIONS[$profile->risk_tolerance] ?? $profile->risk_tolerance;

        return "¡Listo! Con esto arranco tu perfil: ganas \${$income} al mes, planeas invertir \${$invest}, ".
            "tu meta es a {$years} años y tu perfil de riesgo es \"{$risk}\". ".
            'Puedes continuar y ajustarlo después.';
    }
}
