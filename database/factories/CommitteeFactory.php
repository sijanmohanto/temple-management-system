<?php

namespace Database\Factories;

use App\Models\Committee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2030);

        return [
            'code' => 'COM-'.$year.'-'.fake()->unique()->numerify('##'),
            'name_en' => 'Executive Committee '.$year,
            'name_bn' => 'কার্যনির্বাহী পরিষদ '.$year,
            'description_en' => fake()->sentence(),
            'description_bn' => null,
            'term_start_date' => $year.'-01-01',
            'term_end_date' => ($year + 2).'-12-31',
            'status' => 'active',
        ];
    }
}
