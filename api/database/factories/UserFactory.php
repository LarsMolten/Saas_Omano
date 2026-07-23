<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function prestataire(): static
    {
        return $this->state(fn () => [
            'role' => 'prestataire',
            'bio' => fake()->paragraph(),
            'category' => fake()->randomElement([
                'Traiteur', 'Décoration', 'Photographe', 'DJ & Musique',
                'Wedding Planner', 'Fleuriste', 'Pâtissier', 'Sonorisation',
                'Vidéaste', 'Maquillage', 'Coiffure', 'Location matériel',
            ]),
            'city' => fake()->randomElement([
                'Muscat', 'Salalah', 'Sohar', 'Nizwa', 'Sur',
                'Seeb', 'Ibri', 'Barka', 'Rustaq', 'Ibra',
            ]),
            'latitude' => fake()->latitude(20.0, 25.0),
            'longitude' => fake()->longitude(52.0, 60.0),
            'average_rating' => fake()->randomFloat(2, 0, 5),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => 'suspended',
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn () => [
            'status' => 'banned',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
