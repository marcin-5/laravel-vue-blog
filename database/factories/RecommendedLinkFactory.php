<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\RecommendedLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendedLink>
 */
class RecommendedLinkFactory extends Factory
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
            'url' => $this->faker->url(),
            'name' => $this->faker->optional()->company(),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
