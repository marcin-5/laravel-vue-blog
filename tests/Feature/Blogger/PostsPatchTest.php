<?php

namespace Tests\Feature\Blogger;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsPatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_patch_to_posts_update_without_id_returns_405(): void
    {
        // Try to PATCH /posts (without ID) while unauthenticated
        $response = $this->patch('/posts');

        // It should be 405 Method Not Allowed
        $response->assertStatus(405);
    }

    public function test_posts_update_redirects_to_login_when_unauthenticated(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->patch(route('posts.update', $post->id));

        $response->assertRedirect('/login');
    }

    public function test_posts_update_redirects_to_login_when_unauthenticated_and_inertia(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->patch(route('posts.update', $post->id), [], [
            'X-Inertia' => 'true',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_posts_update_returns_401_when_unauthenticated_and_json_requested(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->patch(route('posts.update', $post->id), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}
