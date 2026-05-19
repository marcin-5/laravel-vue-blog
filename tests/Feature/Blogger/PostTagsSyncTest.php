<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

it('syncs tags on post create and update using tag slugs', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for(User::factory()->create())->create();

    $php = Tag::factory()->for($blog)->create(['name' => 'PHP']);
    $laravel = Tag::factory()->for($blog)->create(['name' => 'Laravel']);
    $react = Tag::factory()->for($otherBlog)->create(['name' => 'React']);

    actingAs($owner);

    // Create with tags
    post(route('posts.store'), [
        'blog_id' => $blog->id,
        'title' => 'My Post',
        'content' => '...',
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'tags' => [$php->slug, $laravel->slug, $react->slug], // other blog tag should be ignored
    ])->assertRedirect();

    $post = Post::query()->where('blog_id', $blog->id)->latest()->firstOrFail();
    $post->load('tags');
    expect($post->tags->pluck('slug')->all())
        ->toMatchArray([$php->slug, $laravel->slug])
        ->not->toContain($react->slug);

    // Update tags (switch to only one)
    patch(route('posts.update', $post), [
        'tags' => [$laravel->slug],
    ])->assertRedirect();

    $post->refresh()->load('tags');
    expect($post->tags->pluck('slug')->all())->toMatchArray([$laravel->slug]);
});
