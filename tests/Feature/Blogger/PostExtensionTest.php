<?php

namespace Tests\Feature\Blogger;

use App\Models\Blog;
use App\Models\Post;
use App\Models\PostExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostExtensionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    public function test_blogger_can_create_post_extension(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('post-extensions.store', $this->post), [
            'title' => 'New Extension',
            'content' => 'Extension content in markdown',
            'is_published' => true,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'title' => 'New Extension',
            'is_published' => true,
        ]);
    }

    public function test_blogger_can_update_their_post_extension(): void
    {
        $this->actingAs($this->user);
        $extension = PostExtension::create([
            'post_id' => $this->post->id,
            'title' => 'Old Title',
            'content' => 'Old content',
            'is_published' => false,
        ]);

        $response = $this->patch(route('post-extensions.update', $extension), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'is_published' => true,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('post_extensions', [
            'id' => $extension->id,
            'title' => 'Updated Title',
            'is_published' => true,
        ]);
    }

    public function test_blogger_can_delete_their_post_extension(): void
    {
        $this->actingAs($this->user);
        $extension = PostExtension::create([
            'post_id' => $this->post->id,
            'title' => 'To Delete',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->delete(route('post-extensions.destroy', $extension));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('post_extensions', ['id' => $extension->id]);
    }

    public function test_user_cannot_update_others_post_extension(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $extension = PostExtension::create([
            'post_id' => $this->post->id,
            'title' => 'Original',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->patch(route('post-extensions.update', $extension), [
            'title' => 'Hacked',
            'content' => 'Content',
        ]);

        $response->assertStatus(403);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $blog = Blog::factory()->create(['user_id' => $this->user->id]);
        $this->post = Post::factory()->create(['blog_id' => $blog->id]);
    }
}
