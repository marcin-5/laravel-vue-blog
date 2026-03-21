<?php

namespace Database\Factories;

use App\Models\ExternalLink;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalLink>
 */
class ExternalLinkFactory extends Factory
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
            'title' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'reason' => $this->faker->sentence(),
            'display_order' => 0,
        ];
    }
}
