<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'provider_id' => User::factory()->state(fn () => ['role' => 'prestataire']),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'price_type' => fake()->randomElement(['fixed', 'from', 'quote']),
            'position' => fake()->numberBetween(1, 100),
        ];
    }
}
