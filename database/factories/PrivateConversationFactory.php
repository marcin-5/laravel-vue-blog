<?php

namespace Database\Factories;

use App\Models\PrivateConversation;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivateConversation>
 */
class PrivateConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blog_id' => Blog::factory()->for(User::factory(), 'user'),
            'group_id' => null,
            'post_id' => Post::factory(),
            'initiator_id' => User::factory(),
            'owner_id' => User::factory(),
            'subject' => fake()->sentence(),
        ];
    }
}
