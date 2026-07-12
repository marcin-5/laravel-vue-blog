<?php

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.domain', 'osobliwy.pl');
});

test('blog landing page uses seo_description if provided', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'seo-blog',
        'is_published' => true,
        'seo_description' => 'This is a custom SEO description for the blog landing page.',
        'description' => 'Regular blog description',
        'locale' => 'pl',
    ]);

    $response = $this->get('http://seo-blog.osobliwy.pl');

    $response->assertStatus(200);
    // In Inertia, SEO data is usually passed in the 'seo' prop
    $response->assertInertia(fn($page) => $page
        ->where('seo.description', 'This is a custom SEO description for the blog landing page.')
    );
});

test('blog about page uses about_seo_description if provided', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'seo-blog-about',
        'is_published' => true,
        'about_seo_description' => 'Specific about SEO description.',
        'about' => 'Some about content',
        'locale' => 'pl',
    ]);

    $response = $this->get('http://seo-blog-about.osobliwy.pl/about');

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->where('seo.description', 'Specific about SEO description.')
    );
});

test('blog landing page falls back to regular description if seo_description is null', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'fallback-blog',
        'is_published' => true,
        'seo_description' => null,
        'description' => 'This is the regular blog description that should be used as fallback.',
        'locale' => 'pl',
    ]);

    $response = $this->get('http://fallback-blog.osobliwy.pl');

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->where('seo.description', 'This is the regular blog description that should be used as fallback.')
    );
});
