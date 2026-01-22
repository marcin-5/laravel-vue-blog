<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);

    // Blog belongs to another user (robustness check)
    $this->otherUser = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->otherUser->id]);

    $this->post = Post::factory()->create([
        'group_id' => $this->group->id,
        'blog_id' => $this->blog->id,
        'visibility' => Post::VIS_PUBLIC
    ]);

    $this->extension = Post::factory()->create([
        'group_id' => $this->group->id,
        'blog_id' => $this->blog->id,
        'visibility' => Post::VIS_EXTENSION,
        'title' => 'Group Extension'
    ]);
});

describe('group post extensions', function () {
    it('returns available extensions from the same group', function () {
        // Extension in a different group/blog
        Post::factory()->create([
            'blog_id' => $this->blog->id,
            'title' => 'Other Extension',
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($this->user)
            ->getJson(route('blogger.posts.extensions.available', $this->post))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Group Extension'])
            ->assertJsonMissing(['title' => 'Other Extension']);
    });

    it('still works for blog posts (without group)', function () {
        Post::query()->delete();

        $blogPost = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'title' => 'Blog Post',
            'visibility' => Post::VIS_PUBLIC,
        ]);

        $blogExtension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'title' => 'Blog Extension',
            'visibility' => Post::VIS_EXTENSION,
        ]);

        // We need to act as owner of the blog for this one
        actingAs($this->otherUser)
            ->getJson(route('blogger.posts.extensions.available', $blogPost))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Blog Extension']);
    });

    it('allows owner of the group to manage extensions even if they dont own the blog', function () {
        actingAs($this->user)
            ->postJson(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $this->extension->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $this->extension->id,
        ]);

        actingAs($this->user)
            ->deleteJson(route('blogger.posts.extensions.detach', [$this->post, $this->extension->id]))
            ->assertOk();

        $this->assertDatabaseMissing('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $this->extension->id,
        ]);
    });
});
