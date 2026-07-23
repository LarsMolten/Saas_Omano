<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        return [
            'provider_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'event_date' => fake()->optional(0.7)->dateTimeBetween('-2 years', 'now'),
            'location' => fake()->optional()->city(),
            'budget_approx' => fake()->optional()->randomFloat(2, 50, 5000),
            'position' => fake()->unique()->numberBetween(1, 100),
        ];
    }
}
