<?php

use App\Models\Blog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('displays correct breadcrumb label and external flag based on domain', function () {
    $owner = User::factory()->create();

    // Polish blog
    $plBlog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'locale' => 'pl',
        'slug' => 'test-pl-blog',
    ]);

    // English blog
    $enBlog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'locale' => 'en',
        'slug' => 'test-en-blog',
    ]);

    // Test Polish blog
    $this->get(getBlogUrl($plBlog))
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.label', 'Osobliwy Blog')
            ->where('navigation.breadcrumbs.0.is_external', true)
        );

    // Test English blog
    $this->get(getBlogUrl($enBlog))
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.label', 'Peculiar Matters')
            ->where('navigation.breadcrumbs.0.is_external', true)
        );
});

it('sets is_external to false when on main domain', function () {
    $user = User::factory()->create();
    $group = \App\Models\Group::factory()->create([
        'user_id' => $user->id,
        'is_published' => true,
        'slug' => 'test-group',
    ]);

    // Explicitly set the host to main domain to test non-external breadcrumb
    $domain = config('app.domain');
    $url = "http://{$domain}/_/{$group->slug}";

    $this->actingAs($user)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.is_external', false)
        );
});

it('separates navigation cache based on domain status (internal vs external)', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'locale' => 'pl',
        'slug' => 'cache-test-blog',
    ]);

    // 1. Visit blog on subdomain - should cache with is_external = true
    $urlSubdomain = 'http://' . $blog->slug . '.' . config('app.domain');
    $this->get($urlSubdomain)
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.label', 'Osobliwy Blog')
            ->where('navigation.breadcrumbs.0.is_external', true)
        );

    // 2. Visit the same blog on main domain - should cache with is_external = false
    // We need to bypass the redirect for this test or just simulate the request
    $urlMain = 'http://' . config('app.domain') . '/' . $blog->slug;

    // Using a group instead of blog to test main domain without redirects
    $group = \App\Models\Group::factory()->create([
        'user_id' => $owner->id,
        'is_published' => true,
        'slug' => 'cache-test-group',
    ]);

    $urlGroup = 'http://' . config('app.domain') . '/_/' . $group->slug;
    $this->actingAs($owner)->get($urlGroup)
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.is_external', false)
        );

    // 3. Visit English version of the main domain for the same group
    $urlGroupEn = 'http://' . config('app.domain_secondary') . '/_/' . $group->slug;
    $this->actingAs($owner)->get($urlGroupEn)
        ->assertInertia(fn(Assert $page) => $page
            ->where('navigation.breadcrumbs.0.label', 'Peculiar Matters')
            ->where('navigation.breadcrumbs.0.is_external', false)
        );
});
