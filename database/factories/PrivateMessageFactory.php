<?php

namespace Database\Factories;

use App\Models\PrivateMessage;
use App\Models\PrivateConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivateMessage>
 */
class PrivateMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'private_conversation_id' => PrivateConversation::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
        ];
    }
}
