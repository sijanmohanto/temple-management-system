<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'family_code' => 'FAM-'.fake()->unique()->numerify('####'),
            'family_name' => fake()->lastName().' Family',
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'area' => fake()->randomElement(['Uttara', 'Dhanmondi', 'Gulshan', 'Lalbagh', 'Mirpur']),
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }
}
