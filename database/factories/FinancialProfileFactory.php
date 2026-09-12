<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\FinancialProfile>
 */
class FinancialProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'monthly_income' => fake()->randomFloat(2, 10000, 50000),
            'monthly_expenses' => fake()->randomFloat(2, 5000, 30000),
            'savings' => fake()->randomFloat(2, 0, 100000),
            'risk_tolerance' => fake()->randomElement(['conservative', 'moderate', 'aggressive']),
            'investment_horizon_months' => fake()->numberBetween(6, 360),
        ];
    }
}
