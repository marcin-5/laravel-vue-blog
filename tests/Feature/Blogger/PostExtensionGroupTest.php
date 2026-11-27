<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
            ->get(route('blogger.posts.extensions.index', ['post' => $this->post->id, 'context' => 'group']))
            ->assertSuccessful()
            ->assertInertia(fn(Assert $page) => $page
                ->where('context', 'group')
                ->has('availableExtensions', 1)
                ->where('availableExtensions.0.title', 'Group Extension')
                ->where('backUrl', route('blogger.groups.content.index')));
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
            ->get(route('blogger.posts.extensions.index', $blogPost))
            ->assertSuccessful()
            ->assertInertia(fn(Assert $page) => $page
                ->where('context', 'blog')
                ->has('availableExtensions', 1)
                ->where('availableExtensions.0.title', 'Blog Extension'));
    });

    it('allows owner of the group to manage extensions even if they dont own the blog', function () {
        actingAs($this->user)
            ->post(route('blogger.posts.extensions.attach', ['post' => $this->post->id, 'context' => 'group']), [
                'extension_post_id' => $this->extension->id,
            ])
            ->assertRedirect(route('blogger.posts.extensions.index', ['post' => $this->post->id, 'context' => 'group']));

        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $this->extension->id,
        ]);

        actingAs($this->user)
            ->delete(route('blogger.posts.extensions.detach', [
                'post' => $this->post->id,
                'extensionPostId' => $this->extension->id,
                'context' => 'group',
            ]))
            ->assertRedirect(route('blogger.posts.extensions.index', ['post' => $this->post->id, 'context' => 'group']));

        $this->assertDatabaseMissing('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $this->extension->id,
        ]);
    });
});
