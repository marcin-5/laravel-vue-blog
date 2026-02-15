<?php

namespace Database\Factories;

use App\Models\AnonymousView;
use App\Models\Post;
use App\Models\UserAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnonymousView>
 */
class AnonymousViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_agent_id' => UserAgent::factory(),
            'viewable_type' => (new Post)->getMorphClass(),
            'viewable_id' => Post::factory(),
            'hits' => $this->faker->numberBetween(1, 100),
            'last_seen_at' => now(),
        ];
    }
}
