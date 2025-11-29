<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Random\RandomException;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * @throws RandomException
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'blog_id' => Blog::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(random_int(1, 3)),
            'content' => $this->faker->paragraphs(3, true),
            'is_published' => true,
            'visibility' => Post::VIS_PUBLIC,
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
