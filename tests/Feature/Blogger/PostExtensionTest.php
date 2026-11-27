<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->user->id]);
    $this->post = Post::factory()->create(['blog_id' => $this->blog->id]);
});

describe('post extension basics', function () {
    it('can create a post with extension visibility', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        expect($extension->visibility)->toBe(Post::VIS_EXTENSION);
    });

    it('excludes extension posts from public listing', function () {
        Post::query()->delete();

        Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_PUBLIC,
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
            'published_at' => now(),
        ]);

        $publicPosts = Post::forPublicListing()->get();

        expect($publicPosts)->toHaveCount(1)
            ->and($publicPosts->first()->visibility)->toBe(Post::VIS_PUBLIC);
    });

    it('allows same extension to be attached to multiple posts', function () {
        $post2 = Post::factory()->create(['blog_id' => $this->blog->id]);
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->post->extensions()->attach($extension->id, ['display_order' => 1]);
        $post2->extensions()->attach($extension->id, ['display_order' => 1]);

        expect($extension->fresh()->parentPosts)->toHaveCount(2);
    });
});

describe('post extension management actions', function () {
    it('renders assigned and available extensions as Inertia props', function () {
        $attached = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
            'title' => 'Attached extension',
        ]);
        $available = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
            'title' => 'Available extension',
        ]);
        $this->post->extensions()->attach($attached->id, ['display_order' => 1]);

        actingAs($this->user)
            ->get(route('blogger.posts.extensions.index', $this->post))
            ->assertSuccessful()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/blogger/PostExtensions')
                ->where('post.id', $this->post->id)
                ->where('context', 'blog')
                ->has('extensions', 1)
                ->where('extensions.0.id', $attached->id)
                ->has('availableExtensions', 1)
                ->where('availableExtensions.0.id', $available->id));
    });

    it('can attach an extension to a post through an Inertia redirect', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($this->user)
            ->post(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $extension->id,
                'display_order' => 1,
            ])
            ->assertRedirect(route('blogger.posts.extensions.index', $this->post))
            ->assertSessionHas('success', __('blogger.extensions.attached_success'));

        expect($this->post->fresh()->extensions)->toHaveCount(1);
        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $extension->id,
            'display_order' => 1,
        ]);
    });

    it('can detach an extension from a post through an Inertia redirect', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->post->extensions()->attach($extension->id);

        actingAs($this->user)
            ->delete(route('blogger.posts.extensions.detach', [$this->post, $extension->id]))
            ->assertRedirect(route('blogger.posts.extensions.index', $this->post))
            ->assertSessionHas('success', __('blogger.extensions.detached_success'));

        expect($this->post->fresh()->extensions)->toHaveCount(0);
    });

    it('rejects detaching an extension that is not attached to this post', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($this->user)
            ->delete(route('blogger.posts.extensions.detach', [$this->post, $extension->id]))
            ->assertSessionHasErrors(['extension_post_id']);
    });

    it('can reorder extensions through an Inertia redirect', function () {
        $ext1 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $ext2 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);

        $this->post->extensions()->attach($ext1->id, ['display_order' => 1]);
        $this->post->extensions()->attach($ext2->id, ['display_order' => 2]);

        actingAs($this->user)
            ->put(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [
                    ['id' => $ext1->id, 'display_order' => 2],
                    ['id' => $ext2->id, 'display_order' => 1],
                ],
            ])
            ->assertRedirect(route('blogger.posts.extensions.index', $this->post))
            ->assertSessionHas('success', __('blogger.extensions.reordered_success'));

        $extensions = $this->post->fresh()->extensions;
        expect($extensions->first()->id)->toBe($ext2->id)
            ->and($extensions->last()->id)->toBe($ext1->id);
    });
});

describe('extension selection', function () {
    it('excludes attached extensions and the current post from available props', function () {
        $attached = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
            'title' => 'Attached extension',
        ]);
        $this->post->extensions()->attach($attached->id);
        $this->post->update(['visibility' => Post::VIS_EXTENSION]);

        actingAs($this->user)
            ->get(route('blogger.posts.extensions.index', $this->post))
            ->assertInertia(fn(Assert $page) => $page
                ->where('availableExtensions', [])
                ->where('extensions.0.id', $attached->id));
    });
});

describe('authorization and validation', function () {
    it('prevents unauthorized users from attaching extensions', function () {
        $otherUser = User::factory()->create();
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($otherUser)
            ->post(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $extension->id,
            ])
            ->assertForbidden();
    });

    it('prevents users without post access from viewing extension management', function () {
        $otherUser = User::factory()->create();

        actingAs($otherUser)
            ->get(route('blogger.posts.extensions.index', $this->post))
            ->assertForbidden();
    });

    it('prevents unauthorized users from detaching extensions', function () {
        $otherUser = User::factory()->create();
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);
        $this->post->extensions()->attach($extension->id);

        actingAs($otherUser)
            ->delete(route('blogger.posts.extensions.detach', [$this->post, $extension->id]))
            ->assertForbidden();
    });

    it('prevents unauthorized users from reordering extensions', function () {
        $otherUser = User::factory()->create();
        $ext1 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $this->post->extensions()->attach($ext1->id, ['display_order' => 1]);

        actingAs($otherUser)
            ->put(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [['id' => $ext1->id, 'display_order' => 2]],
            ])
            ->assertForbidden();
    });

    it('requires a valid extension_post_id when attaching', function () {
        actingAs($this->user)
            ->post(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => 99999,
            ])
            ->assertSessionHasErrors(['extension_post_id']);
    });

    it('rejects extensions that do not belong to the post when attaching', function () {
        $otherBlog = Blog::factory()->for(User::factory())->create();
        $extension = Post::factory()->create([
            'blog_id' => $otherBlog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($this->user)
            ->post(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $extension->id,
            ])
            ->assertSessionHasErrors(['extension_post_id']);
    });

    it('validates reorder structure and attached extension membership', function () {
        actingAs($this->user)
            ->put(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => 'not-an-array',
            ])
            ->assertSessionHasErrors(['extensions']);

        actingAs($this->user)
            ->put(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [['id' => $this->post->id]], // missing display_order
            ])
            ->assertSessionHasErrors(['extensions.0.display_order']);

        $extension = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $this->post->extensions()->attach($extension->id, ['display_order' => 1]);
        $otherExtension = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);

        actingAs($this->user)
            ->put(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [['id' => $otherExtension->id, 'display_order' => 1]],
            ])
            ->assertSessionHasErrors(['extensions.0.id']);
    });
});
