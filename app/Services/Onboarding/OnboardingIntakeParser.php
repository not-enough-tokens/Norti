<?php

namespace App\Services\Onboarding;

/**
 * Convierte las respuestas en lenguaje natural del chat de onboarding a los
 * valores numéricos que necesita `financial_profiles`. No usa el LLM (M5):
 * el flujo es de preguntas fijas, así que un parser determinista basta y no
 * depende de OPENAI_API_KEY.
 */
class OnboardingIntakeParser
{
    private const SMALL_NUMBERS = [
        'un' => 1, 'uno' => 1, 'una' => 1,
        'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5,
        'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10,
    ];

    /**
     * "50,000 pesos" -> 50000.0, "20 mil" -> 20000.0, "1.5 millones" -> 1500000.0.
     */
    public function parseAmount(string $text): ?float
    {
        $normalized = mb_strtolower(trim($text));

        if (preg_match('/\d[\d.,]*/', $normalized, $match) !== 1) {
            return null;
        }

        $raw = str_replace(',', '', $match[0]);
        $value = (float) $raw;

        $multiplier = match (true) {
            (bool) preg_match('/mill[oó]n/u', $normalized) => 1_000_000,
            (bool) preg_match('/\bmil\b/u', $normalized) => 1_000,
            (bool) preg_match('/\d\s*k\b/u', $normalized) => 1_000,
            default => 1,
        };

        return $value * $multiplier;
    }

    /**
     * "2 años" -> 24, "18 meses" -> 18, "un año" -> 12. Un número sin unidad
     * se asume en años, porque así lo pide la pregunta fija ("1 año, 2 años, etc.").
     */
    public function parseHorizonMonths(string $text): ?int
    {
        $normalized = mb_strtolower(trim($text));

        $number = null;

        if (preg_match('/\d+(\.\d+)?/', $normalized, $match) === 1) {
            $number = (float) $match[0];
        } else {
            foreach (self::SMALL_NUMBERS as $word => $value) {
                if (preg_match('/\b'.$word.'\b/u', $normalized) === 1) {
                    $number = (float) $value;
                    break;
                }
            }
        }

        if ($number === null) {
            return null;
        }

        $months = str_contains($normalized, 'mes') ? $number : $number * 12;

        return max(1, (int) round($months));
    }
}
