<?php

namespace Tests\Feature\Blogger;

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostExtensionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Blog $blog;
    private Post $post;

    public function test_can_create_a_post_with_extension_visibility(): void
    {
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->assertEquals(Post::VIS_EXTENSION, $extension->visibility);
    }

    public function test_can_attach_extension_to_a_post(): void
    {
        $this->actingAs($this->user);

        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $response = $this->post(route('blogger.posts.extensions.attach', [
            'blog' => $this->blog->id,
            'post' => $this->post->id,
        ]), [
            'extension_post_id' => $extension->id,
            'display_order' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('post_extensions', [
            'post_id' => $this->post->id,
            'extension_post_id' => $extension->id,
            'display_order' => 1,
        ]);

        $this->assertCount(1, $this->post->fresh()->extensions);
    }

    public function test_can_detach_extension_from_a_post(): void
    {
        $this->actingAs($this->user);

        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->post->extensions()->attach($extension->id);

        $response = $this->delete(route('blogger.posts.extensions.detach', [
            'blog' => $this->blog->id,
            'post' => $this->post->id,
            'extensionPostId' => $extension->id,
        ]));

        $response->assertStatus(200);
        $this->assertCount(0, $this->post->fresh()->extensions);
    }

    public function test_can_reorder_extensions(): void
    {
        $this->actingAs($this->user);

        $ext1 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);
        $ext2 = Post::factory()->create(['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION]);

        $this->post->extensions()->attach($ext1->id, ['display_order' => 1]);
        $this->post->extensions()->attach($ext2->id, ['display_order' => 2]);

        $response = $this->put(route('blogger.posts.extensions.reorder', [
            'blog' => $this->blog->id,
            'post' => $this->post->id,
        ]), [
            'extensions' => [
                ['id' => $ext1->id, 'display_order' => 2],
                ['id' => $ext2->id, 'display_order' => 1],
            ],
        ]);

        $response->assertStatus(200);

        $extensions = $this->post->fresh()->extensions;
        $this->assertEquals($ext2->id, $extensions->first()->id);
        $this->assertEquals($ext1->id, $extensions->last()->id);
    }

    public function test_excludes_extension_posts_from_public_listing(): void
    {
        // Clear posts created in setUp to have a clean state for this test
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

        $this->assertCount(1, $publicPosts);
        $this->assertEquals(Post::VIS_PUBLIC, $publicPosts->first()->visibility);
    }

    public function test_allows_same_extension_to_be_attached_to_multiple_posts(): void
    {
        $post2 = Post::factory()->create(['blog_id' => $this->blog->id]);
        $extension = Post::factory()->create([
            'blog_id' => $this->blog->id,
            'visibility' => Post::VIS_EXTENSION,
        ]);

        $this->post->extensions()->attach($extension->id, ['display_order' => 1]);
        $post2->extensions()->attach($extension->id, ['display_order' => 1]);

        $this->assertCount(2, $extension->fresh()->parentPosts);
    }

    public function test_available_extensions_exclude_already_attached(): void
    {
        $this->actingAs($this->user);

        $ext1 = Post::factory()->create(
            ['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION, 'title' => 'Ext 1'],
        );
        $ext2 = Post::factory()->create(
            ['blog_id' => $this->blog->id, 'visibility' => Post::VIS_EXTENSION, 'title' => 'Ext 2'],
        );

        $this->post->extensions()->attach($ext1->id);

        $response = $this->getJson(route('blogger.posts.extensions.available', [
            'blog' => $this->blog->id,
            'post' => $this->post->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => 'Ext 2']);
        $response->assertJsonMissing(['title' => 'Ext 1']);
    }

    public function test_available_extensions_exclude_self(): void
    {
        $this->actingAs($this->user);

        // Make the post itself an extension type to test if it's excluded
        $this->post->update(['visibility' => Post::VIS_EXTENSION]);

        $response = $this->getJson(route('blogger.posts.extensions.available', [
            'blog' => $this->blog->id,
            'post' => $this->post->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonMissing(['title' => $this->post->title]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->blog = Blog::factory()->create(['user_id' => $this->user->id]);
        $this->post = Post::factory()->create(['blog_id' => $this->blog->id]);
    }
}
