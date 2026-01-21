<?php

namespace Tests\Feature\Blogger;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostUpdatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_post_without_blog_but_with_group(): void
    {
        $user = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $user->id]);

        // Post belongs to group but blog_id is null
        $post = Post::factory()->create([
            'blog_id' => null,
            'group_id' => $group->id,
            'title' => 'Post without blog',
        ]);

        $response = $this->actingAs($user)->patch(route('posts.update', $post), [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }
}
