<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TagLandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TagLandingPage>
 */
class TagLandingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag_id' => Tag::factory(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
