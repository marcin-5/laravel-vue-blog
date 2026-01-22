<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;

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
    it('can attach extension to a post', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        actingAs($this->user)
            ->postJson(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $extension->id,
                'display_order' => 1,
            ])
            ->assertOk();

        expect($this->post->fresh()->extensions)->toHaveCount(1);
        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $extension->id,
            'display_order' => 1,
        ]);
    });

    it('can detach extension from a post', function () {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->post->extensions()->attach($extension->id);

        actingAs($this->user)
            ->deleteJson(route('blogger.posts.extensions.detach', [$this->post, $extension->id]))
            ->assertOk();

        expect($this->post->fresh()->extensions)->toHaveCount(0);
    });

    it('can reorder extensions', function () {
        $ext1 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $ext2 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);

        $this->post->extensions()->attach($ext1->id, ['display_order' => 1]);
        $this->post->extensions()->attach($ext2->id, ['display_order' => 2]);

        actingAs($this->user)
            ->putJson(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [
                    ['id' => $ext1->id, 'display_order' => 2],
                    ['id' => $ext2->id, 'display_order' => 1],
                ],
            ])
            ->assertOk();

        $extensions = $this->post->fresh()->extensions;
        expect($extensions->first()->id)->toBe($ext2->id)
            ->and($extensions->last()->id)->toBe($ext1->id);
    });
});

describe('available extensions listing', function () {
    it('excludes already attached extensions from available list', function () {
        $ext1 = Post::factory()->create(
            ['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION, 'title' => 'Ext 1'],
        );
        $ext2 = Post::factory()->create(
            ['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION, 'title' => 'Ext 2'],
        );

        $this->post->extensions()->attach($ext1->id);

        actingAs($this->user)
            ->getJson(route('blogger.posts.extensions.available', $this->post))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Ext 2'])
            ->assertJsonMissing(['title' => 'Ext 1']);
    });

    it('excludes self from available extensions', function () {
        $this->post->update(['visibility' => Post::VIS_EXTENSION]);

        actingAs($this->user)
            ->getJson(route('blogger.posts.extensions.available', $this->post))
            ->assertOk()
            ->assertJsonMissing(['title' => $this->post->title]);
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
            ->postJson(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => $extension->id,
            ])
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
            ->deleteJson(route('blogger.posts.extensions.detach', [$this->post, $extension->id]))
            ->assertForbidden();
    });

    it('prevents unauthorized users from reordering extensions', function () {
        $otherUser = User::factory()->create();
        $ext1 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $this->post->extensions()->attach($ext1->id, ['display_order' => 1]);

        actingAs($otherUser)
            ->putJson(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [['id' => $ext1->id, 'display_order' => 2]],
            ])
            ->assertForbidden();
    });

    it('requires valid extension_post_id when attaching', function () {
        actingAs($this->user)
            ->postJson(route('blogger.posts.extensions.attach', $this->post), [
                'extension_post_id' => 99999,
            ])
            ->assertJsonValidationErrors(['extension_post_id']);
    });

    it('validates reorder structure', function () {
        actingAs($this->user)
            ->putJson(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => 'not-an-array',
            ])
            ->assertJsonValidationErrors(['extensions']);

        actingAs($this->user)
            ->putJson(route('blogger.posts.extensions.reorder', $this->post), [
                'extensions' => [['id' => $this->post->id]], // missing display_order
            ])
            ->assertJsonValidationErrors(['extensions.0.display_order']);
    });
});
