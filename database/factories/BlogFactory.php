<?php

namespace Database\Factories;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Random\RandomException;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'user_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(random_int(1, 5)),
            'seo_description' => $this->faker->boolean(50) ? $this->faker->text(160) : null,
            'about_seo_description' => $this->faker->boolean(50) ? $this->faker->text(160) : null,
            'contact_seo_description' => $this->faker->boolean(50) ? $this->faker->text(160) : null,
            'is_published' => $this->faker->boolean(70),
            'is_multi_author' => $this->faker->boolean(30),
            'locale' => $this->faker->randomElement(['en', 'pl']),
            'sidebar' => $this->faker->randomElement([random_int(-40, -25), random_int(25, 40), 0]),
        ];
    }
}
