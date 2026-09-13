<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\EducationalTopic;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuario y datos de demo para M8 -- ver docs/development/demo-script.md.
 *
 * Deliberadamente sin FinancialGoal: así get_learning_path recomienda por la
 * regla `no_goals`, el punto de entrada más natural para narrar el "por qué"
 * de una recomendación en la demo. El portafolio tiene 3 activos distintos
 * (2 acciones + 1 bono) para que la regla de concentración no se dispare y
 * no compita con la narrativa de `no_goals`.
 *
 * Idempotente (firstOrCreate/updateOrCreate): correr este seeder varias
 * veces no duplica nada.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EducationalTopicSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'demo@banorte.local'],
            ['name' => 'Usuario Demo', 'password' => bcrypt('password')]
        );

        FinancialProfile::updateOrCreate(['user_id' => $user->id], [
            'monthly_income' => 35000,
            'monthly_expenses' => 22000,
            'savings' => 80000,
            'risk_tolerance' => 'moderate',
            'investment_horizon_months' => 60,
        ]);

        $portfolio = Portfolio::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Portafolio Demo'],
            ['description' => 'Sembrado por DemoSeeder para la demo de M8']
        );

        $holdings = [
            'AAPL' => ['name' => 'Apple Inc.', 'asset_type' => 'accion', 'currency' => 'USD', 'quantity' => 10, 'average_cost' => 180],
            'MSFT' => ['name' => 'Microsoft Corp.', 'asset_type' => 'accion', 'currency' => 'USD', 'quantity' => 5, 'average_cost' => 300],
            // BND (bond ETF) en vez de CETES28: Twelve Data no cotiza CETES
            // (instrumento gubernamental mexicano fuera de su cobertura), así
            // que el agente insistía en advertir "sin cotización disponible"
            // en cada respuesta -- ruidoso para la demo. BND sí tiene precio
            // en vivo y sigue clasificando como "bono" para el análisis de
            // riesgo/diversificación.
            'BND' => ['name' => 'Vanguard Total Bond Market ETF', 'asset_type' => 'bono', 'currency' => 'USD', 'quantity' => 20, 'average_cost' => 72],
        ];

        foreach ($holdings as $symbol => $data) {
            $asset = Asset::firstOrCreate(
                ['symbol' => $symbol],
                ['name' => $data['name'], 'asset_type' => $data['asset_type'], 'currency' => $data['currency']]
            );

            Holding::firstOrCreate(
                ['portfolio_id' => $portfolio->id, 'asset_id' => $asset->id],
                ['quantity' => $data['quantity'], 'average_cost' => $data['average_cost']]
            );
        }

        // Un tema completado (pero no 'ahorro-vs-inversion', que es al que
        // apunta la regla `no_goals` -- completarlo taparía la recomendación
        // que la demo quiere mostrar) para que get_learning_progress muestre
        // avance parcial real en vez de 0%.
        $completedTopic = EducationalTopic::where('slug', 'interes-compuesto')->first();

        if ($completedTopic) {
            $user->educationalTopics()->syncWithoutDetaching([
                $completedTopic->id => ['completed_at' => now()->subDays(3)],
            ]);
        }

        $this->command?->info("Usuario demo: {$user->email} (password: password)");
    }
}
