<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOption>
 */
class ServiceOptionFactory extends Factory
{
    protected $model = ServiceOption::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'label' => fake()->word(),
            'extra_price' => fake()->randomFloat(2, 0, 50),
        ];
    }
}
