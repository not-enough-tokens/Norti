<?php

namespace Database\Factories;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialGoal>
 */
class FinancialGoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $target = fake()->randomFloat(2, 50_000, 1_000_000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Enganche casa', 'Fondo de emergencia', 'Retiro', 'Viaje']),
            'target_amount' => $target,
            'current_amount' => fake()->randomFloat(2, 0, $target),
            'target_day' => fake()->dateTimeBetween('+6 months', '+10 years')->format('Y-m-d'),
            'goal_type' => fake()->randomElement(['casa', 'retiro', 'emergencia', 'viaje']),
        ];
    }

    /**
     * Meta cuya fecha objetivo ya pasó.
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'target_day' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
        ]);
    }
}
