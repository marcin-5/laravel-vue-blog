<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'blog_id' => Blog::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->optional()->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
            'is_published' => true,
            'visibility' => Post::VIS_PUBLIC,
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
