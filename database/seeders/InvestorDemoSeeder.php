<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Segundo usuario de demo (M8) -- complementa a DemoSeeder cubriendo lo que
 * ese usuario deja fuera a propósito:
 *
 * - `get_financial_goals` no tenía ningún dato real que mostrar en ninguna
 *   demo hasta ahora -- aquí sí hay 2 metas.
 * - Con metas registradas, `get_learning_path` ya no puede recomendar por
 *   `no_goals` (esa regla es la primera en `FinancialEducationService::
 *   getRecommendedTopic()`) -- este perfil está armado para caer en la
 *   siguiente regla no demostrada, `conservative_profile_with_stocks`:
 *   `risk_tolerance` normaliza a "conservative" Y el portafolio SÍ tiene una
 *   posición en acción (GOOGL). El portafolio usa 3 activos distintos a
 *   propósito para que la regla de concentración no se dispare primero y
 *   tape la narrativa que sí queremos mostrar.
 *
 * Idempotente (firstOrCreate/updateOrCreate): correr este seeder varias
 * veces no duplica nada.
 */
class InvestorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EducationalTopicSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'inversor@banorte.local'],
            ['name' => 'Inversor Demo', 'password' => bcrypt('password')]
        );

        FinancialProfile::updateOrCreate(['user_id' => $user->id], [
            'monthly_income' => 60000,
            'monthly_expenses' => 38000,
            'savings' => 250000,
            'risk_tolerance' => 'conservative',
            'investment_horizon_months' => 96,
        ]);

        FinancialGoal::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Fondo de retiro'],
            [
                'target_amount' => 2000000,
                'current_amount' => 350000,
                'target_day' => now()->addYears(8),
                'goal_type' => 'retiro',
            ]
        );

        FinancialGoal::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Enganche de casa'],
            [
                'target_amount' => 600000,
                'current_amount' => 180000,
                'target_day' => now()->addYears(3),
                'goal_type' => 'casa',
            ]
        );

        $portfolio = Portfolio::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Portafolio Inversor'],
            ['description' => 'Sembrado por InvestorDemoSeeder para la demo de M8']
        );

        $holdings = [
            'GOOGL' => ['name' => 'Alphabet Inc.', 'asset_type' => 'accion', 'currency' => 'USD', 'quantity' => 3, 'average_cost' => 140],
            'SPY' => ['name' => 'SPDR S&P 500 ETF Trust', 'asset_type' => 'fondo', 'currency' => 'USD', 'quantity' => 2, 'average_cost' => 550],
            // BND en vez de CETES91 -- ver la nota en DemoSeeder: Twelve Data
            // no cotiza instrumentos gubernamentales mexicanos, así que el
            // agente terminaba insistiendo en "sin cotización disponible".
            'BND' => ['name' => 'Vanguard Total Bond Market ETF', 'asset_type' => 'bono', 'currency' => 'USD', 'quantity' => 40, 'average_cost' => 68],
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

        $this->command?->info("Usuario inversor: {$user->email} (password: password)");
    }
}
