<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'person_code' => 'DEV-'.fake()->unique()->numerify('#####'),
            'name_en' => fake()->name(),
            'name_bn' => null,
            'phone' => fake()->unique()->numerify('017########'),
            'email' => fake()->unique()->safeEmail(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'spouse_name' => null,
            'gotra' => fake()->randomElement(['Shandilya', 'Kashyapa', 'Bharadwaja', 'Vatsa', 'Gautama']),
            'occupation' => fake()->jobTitle(),
            'present_address' => fake()->address(),
            'permanent_address' => fake()->address(),
            'status' => 'active',
            'public_notes' => fake()->sentence(),
            'private_notes' => fake()->sentence(),
        ];
    }
}
