<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Proprietário', 'Prestador de Serviços']),
            'slug' => fake()->randomElement(['proprietario', 'prestador']),
        ];
    }

    /**
     * Create a proprietario profile.
     */
    public function proprietario(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Proprietário',
            'slug' => 'proprietario',
        ]);
    }

    /**
     * Create a prestador profile.
     */
    public function prestador(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Prestador de Serviços',
            'slug' => 'prestador',
        ]);
    }
}
