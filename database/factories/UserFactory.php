<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\UserRole;
use App\Models\Unit;

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
        $academicUnitIds = Unit::where('name', '!=', 'PTIK')->pluck('id');

        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(), // Membuat username dummy yang unik
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'), // Semua user punya password 'password'
            'role' => UserRole::USER->value, // Default role adalah User
            'description' => fake()->sentence(), // Membuat kalimat deskripsi dummy
            'remember_token' => Str::random(10),
            'identity_number' => fake()->unique()->numerify('14#######'), // Membuat nomor 8 digit acak
            'unit_id' => $academicUnitIds->isNotEmpty() ? $academicUnitIds->random() : null,
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
}
