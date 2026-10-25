<?php

namespace Database\Factories;

use App\Models\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classification>
 */
class ClassificationFactory extends Factory
{
    protected $model = Classification::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug' => $slug,
            'name_en' => ucfirst($slug),
            'name_bn' => ucfirst($slug),
            'description_en' => fake()->sentence(),
            'description_bn' => null,
            'is_system' => true,
        ];
    }
}
