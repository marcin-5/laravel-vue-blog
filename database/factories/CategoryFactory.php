<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->word;

        return [
            'name' => [
                'en' => ucfirst($word),
                'pl' => ucfirst($word) . ' (PL)',
            ],
            'slug' => Str::slug($word),
        ];
    }
}
