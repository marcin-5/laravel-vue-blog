<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscription>
 */
class NewsletterSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->safeEmail(),
            'blog_id' => Blog::factory(),
            'frequency' => $this->faker->randomElement(['daily', 'weekly']),
            'visitor_id' => $this->faker->uuid(),
        ];
    }
}
