<?php

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.domain_locales', [
        'blog.pl' => 'pl',
        'blog.com' => 'en',
    ]);
    config()->set('app.supported_locales', ['pl', 'en']);
});

it('sets locale based on domain', function () {
    $this
        ->get('http://blog.pl/_test/locale')
        ->assertJson(['locale' => 'pl']);

    $this
        ->get('http://blog.com/_test/locale')
        ->assertJson(['locale' => 'en']);
});

it('filters blogs by domain locale on welcome page', function () {
    $user = User::factory()->create();

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Polski Blog',
        'locale' => 'pl',
        'is_published' => true,
    ]);

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'English Blog',
        'locale' => 'en',
        'is_published' => true,
    ]);

    // On Polish domain, only Polish blog should be visible
    $responsePl = $this->get('http://blog.pl/');
    $responsePl->assertInertia(fn($page) => $page
        ->has('blogs', 1)
        ->where('blogs.0.name', 'Polski Blog'),
    );

    // On English domain, only English blog should be visible
    $responseEn = $this->get('http://blog.com/');
    $responseEn->assertInertia(fn($page) => $page
        ->has('blogs', 1)
        ->where('blogs.0.name', 'English Blog'),
    );
});

it('disables locale filter for admin routes', function () {
    $user = User::factory()->create();

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Polski Blog',
        'locale' => 'pl',
        'is_published' => true,
    ]);

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'English Blog',
        'locale' => 'en',
        'is_published' => true,
    ]);

    // When accessing dashboard, all blogs should be visible
    $this->actingAs($user)->get('http://blog.pl/dashboard');

    $this->assertEquals(2, Blog::count());
});
