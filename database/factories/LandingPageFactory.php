<?php

namespace Database\Factories;

use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Random\RandomException;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            'blog_id' => null,
            'content' => $this->faker->paragraphs(random_int(3, 6), true),
            'sidebar_position' => $this->faker->randomElement([
                LandingPage::SIDEBAR_LEFT,
                LandingPage::SIDEBAR_RIGHT,
                LandingPage::SIDEBAR_NONE,
            ]),
        ];
    }
}
