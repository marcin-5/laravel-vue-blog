<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
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
     * @throws RandomException
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => $this->faker->paragraph(random_int(1, 5)),
            'is_published' => $this->faker->boolean(70),
            'locale' => $this->faker->randomElement(['en', 'pl']),
            'sidebar' => $this->faker->randomElement([random_int(-40, -25), random_int(25, 40), 0]),
        ];
    }
}
