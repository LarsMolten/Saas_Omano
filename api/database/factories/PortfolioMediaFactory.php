<?php

namespace Database\Factories;

use App\Models\PortfolioMedia;
use App\Models\PortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioMediaFactory extends Factory
{
    protected $model = PortfolioMedia::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['image', 'video']);

        return [
            'portfolio_item_id' => PortfolioItem::factory(),
            'type' => $type,
            'url' => 'portfolio/' . fake()->uuid() . ($type === 'image' ? '.jpg' : '.mp4'),
            'url_processed' => null,
            'position' => fake()->unique()->numberBetween(1, 20),
            'processed' => false,
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'type' => 'image',
            'url' => 'portfolio/' . fake()->uuid() . '.jpg',
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn () => [
            'processed' => true,
            'url_processed' => 'portfolio/processed/' . fake()->uuid() . '.jpg',
        ]);
    }
}
