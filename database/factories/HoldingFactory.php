<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Holding;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holding>
 */
class HoldingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'asset_id' => Asset::factory(),
            'quantity' => fake()->randomFloat(6, 1, 100),
            'average_cost' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
