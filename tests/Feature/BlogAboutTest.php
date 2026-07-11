<?php

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.domain', 'osobliwy.pl');
    Config::set('app.domain_secondary', 'osobliwy.com');
});

test('main domain about page shows default content', function () {
    $response = $this->get('http://osobliwy.pl/about');

    $response->assertStatus(200)
        ->assertInertia(fn($page) => $page
            ->component('public/About')
            ->has('aboutHeading')
            ->has('aboutHtml')
        );
});

test('blog subdomain about page shows blog specific content', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-blog',
        'about' => '# About our blog',
        'is_published' => true,
        'locale' => 'pl',
    ]);

    $response = $this->get('http://test-blog.osobliwy.pl/about');

    $response->assertStatus(200)
        ->assertInertia(fn($page) => $page
            ->component('public/blog/About')
            ->where('blog.slug', 'test-blog')
            ->has('blog.aboutHtml')
        );

    expect($response->getContent())->toContain('About our blog');
});

test('blog subdomain about page works even if about content is null', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'null-about-blog',
        'about' => null,
        'is_published' => true,
        'locale' => 'pl',
    ]);

    $response = $this->get('http://null-about-blog.osobliwy.pl/about');

    $response->assertStatus(200);
});
