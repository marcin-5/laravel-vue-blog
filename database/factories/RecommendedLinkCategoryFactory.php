<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\RecommendedLinkCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendedLinkCategory>
 */
class RecommendedLinkCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blog_id' => Blog::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'display_order' => 0,
        ];
    }
}
