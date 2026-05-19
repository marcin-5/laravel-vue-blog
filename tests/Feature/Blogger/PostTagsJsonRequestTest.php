<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

it('accepts tags as JSON string on create and update', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    $php = Tag::factory()->for($blog)->create(['name' => 'PHP']);
    $laravel = Tag::factory()->for($blog)->create(['name' => 'Laravel']);

    actingAs($owner);

    // Create with JSON string tags
    post(route('posts.store'), [
        'blog_id' => $blog->id,
        'title' => 'Tagged Post',
        'content' => '...',
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'tags' => json_encode([$php->slug, $laravel->slug]),
    ])->assertRedirect();

    $post = Post::query()->where('blog_id', $blog->id)->latest()->firstOrFail();
    $post->load('tags');
    expect($post->tags->pluck('slug')->all())
        ->toMatchArray([$php->slug, $laravel->slug]);

    // Update with JSON string to switch to single tag
    patch(route('posts.update', $post), [
        'tags' => json_encode([$laravel->slug]),
    ])->assertRedirect();

    $post->refresh()->load('tags');
    expect($post->tags->pluck('slug')->all())
        ->toMatchArray([$laravel->slug]);
});
