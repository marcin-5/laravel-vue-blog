<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->blog = Blog::factory()->for($this->owner)->create([
        'is_published' => true,
        'locale' => 'pl',
    ]);
    $this->post = Post::factory()->for($this->blog)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
        'visibility' => Post::VIS_PUBLIC,
    ]);
    $this->tag = Tag::factory()->for($this->blog)->create();
});

it('redirects old blog landing URL to subdomain', function () {
    $response = $this->get("/{$this->blog->slug}");

    $mainDomain = config('app.domain');
    $expectedUrl = "http://{$this->blog->slug}.{$mainDomain}";

    $response->assertRedirect($expectedUrl);
    $response->assertStatus(301);
});

it('redirects old post URL to subdomain', function () {
    $response = $this->get("/{$this->blog->slug}/{$this->post->slug}");

    $mainDomain = config('app.domain');
    $expectedUrl = "http://{$this->blog->slug}.{$mainDomain}/{$this->post->slug}";

    $response->assertRedirect($expectedUrl);
    $response->assertStatus(301);
});

it('redirects old tag URL to subdomain', function () {
    $response = $this->get("/{$this->blog->slug}/tags/{$this->tag->slug}");

    $mainDomain = config('app.domain');
    $expectedUrl = "http://{$this->blog->slug}.{$mainDomain}/tags/{$this->tag->slug}";

    $response->assertRedirect($expectedUrl);
    $response->assertStatus(301);
});

it('redirects old blog prefix URL to subdomain', function () {
    $response = $this->get("/blogs/{$this->blog->slug}");

    $mainDomain = config('app.domain');
    $expectedUrl = "http://{$this->blog->slug}.{$mainDomain}";

    $response->assertRedirect($expectedUrl);
    $response->assertStatus(301);
});

it('does not redirect reserved system paths', function () {
    $this->get('/')->assertStatus(200);
    $this->get('/about')->assertStatus(200);
    $this->get('/contact')->assertStatus(200);
    $this->get('/newsletter')->assertStatus(200);
});
