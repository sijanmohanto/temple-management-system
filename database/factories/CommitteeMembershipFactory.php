<?php

namespace Database\Factories;

use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMembership>
 */
class CommitteeMembershipFactory extends Factory
{
    protected $model = CommitteeMembership::class;

    public function definition(): array
    {
        return [
            'committee_id' => Committee::factory(),
            'person_id' => Person::factory(),
            'designation_en' => fake()->randomElement(['President', 'Secretary', 'Treasurer', 'Executive Member']),
            'designation_bn' => null,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->addYears(2)->endOfYear()->toDateString(),
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }
}
