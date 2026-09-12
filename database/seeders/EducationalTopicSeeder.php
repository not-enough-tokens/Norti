<?php

namespace Database\Seeders;

use App\Models\EducationalTopic;
use Illuminate\Database\Seeder;

class EducationalTopicSeeder extends Seeder
{
    public function run(): void
    {
        EducationalTopic::create([
            'title' => 'Ahorro vs inversión',
            'slug' => 'ahorro-vs-inversion',
            'description' => 'Aprende la diferencia entre ahorrar e invertir y cuándo puede ser útil cada opción.',
            'content' => 'Ahorrar significa guardar dinero para utilizarlo posteriormente, mientras que invertir busca generar un rendimiento con ese dinero. El ahorro suele priorizar disponibilidad y estabilidad, mientras que una inversión puede implicar mayor riesgo a cambio de una posible mayor rentabilidad.',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Interés compuesto',
            'slug' => 'interes-compuesto',
            'description' => 'Descubre cómo tus rendimientos pueden generar nuevos rendimientos con el paso del tiempo.',
            'content' => 'El interés compuesto ocurre cuando los rendimientos obtenidos se reinvierten y comienzan a generar nuevos rendimientos. El tiempo puede tener un efecto importante en el crecimiento de una inversión, especialmente cuando las ganancias se mantienen invertidas.',
            'category' => 'basics',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Acciones',
            'slug' => 'acciones',
            'description' => 'Conoce qué son las acciones y qué significa invertir en una empresa.',
            'content' => 'Una acción representa una participación en una empresa. Al comprar acciones, un inversionista puede beneficiarse si el valor de la empresa aumenta y, en algunos casos, puede recibir dividendos. Sin embargo, el precio de las acciones también puede disminuir y existe riesgo de pérdida.',
            'category' => 'investments',
            'difficulty' => 'beginner',
            'estimated_minutes' => 6,
        ]);

        EducationalTopic::create([
            'title' => 'Diversificación',
            'slug' => 'diversificacion',
            'description' => 'Comprende cómo distribuir una inversión entre diferentes activos puede ayudar a administrar el riesgo.',
            'content' => 'Diversificar significa distribuir el dinero entre diferentes inversiones para evitar depender completamente del comportamiento de un solo activo. La diversificación puede ayudar a reducir ciertos riesgos, aunque no elimina la posibilidad de perder dinero.',
            'category' => 'risk',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Riesgo de inversión',
            'slug' => 'riesgo-de-inversion',
            'description' => 'Conoce los principales tipos de riesgo que pueden afectar una inversión.',
            'content' => 'Toda inversión puede tener algún nivel de riesgo. El riesgo representa la posibilidad de que el resultado sea diferente al esperado, incluyendo la posibilidad de perder dinero. Antes de invertir es importante considerar el horizonte de tiempo, los objetivos y la tolerancia al riesgo.',
            'category' => 'risk',
            'difficulty' => 'beginner',
            'estimated_minutes' => 7,
        ]);
    }
}
