<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
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

    /**
     * Create a user with proprietario profile.
     */
    public function proprietario(): static
    {
        return $this->afterCreating(function ($user) {
            $profile = Profile::firstOrCreate(
                ['slug' => 'proprietario'],
                ['name' => 'Proprietário']
            );
            $user->profiles()->attach($profile);
        });
    }

    /**
     * Create a user with prestador profile.
     */
    public function prestador(): static
    {
        return $this->afterCreating(function ($user) {
            $profile = Profile::firstOrCreate(
                ['slug' => 'prestador'],
                ['name' => 'Prestador de Serviços']
            );
            $user->profiles()->attach($profile);
        });
    }

    /**
     * Create a user with both profiles.
     */
    public function withProfiles(): static
    {
        return $this->afterCreating(function ($user) {
            $proprietario = Profile::firstOrCreate(
                ['slug' => 'proprietario'],
                ['name' => 'Proprietário']
            );
            $prestador = Profile::firstOrCreate(
                ['slug' => 'prestador'],
                ['name' => 'Prestador de Serviços']
            );
            $user->profiles()->attach([$proprietario->id, $prestador->id]);
        });
    }
}
