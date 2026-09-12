<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'symbol' => strtoupper(fake()->unique()->lexify('????')),
            'name' => fake()->company(),
            'asset_type' => fake()->randomElement(['accion', 'bono', 'fondo', 'efectivo']),
            'currency' => 'MXN',
        ];
    }
}
