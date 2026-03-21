<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Post;
use App\Models\RelatedPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelatedPost>
 */
class RelatedPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'blog_id' => Blog::factory(),
            'related_post_id' => Post::factory(),
            'reason' => $this->faker->sentence(),
            'display_order' => 0,
        ];
    }
}
