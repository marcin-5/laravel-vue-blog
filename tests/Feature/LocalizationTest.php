<?php

use App\Models\Blog;
use App\Models\User;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\URL;

it('sets locale based on browser preference for guests on generic public pages', function () {
    // English preference
    $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'en'));

    // Polish preference
    $this->get('/', ['Accept-Language' => 'pl-PL,pl;q=0.9'])
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));
});

it('sets locale based on user setting for authenticated users on generic public pages', function () {
    $user = User::factory()->create(['locale' => 'pl']);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));

    $user->update(['locale' => 'en']);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'en'));
});

it('sets locale based on blog setting for landing and post pages', function () {
    $user = User::factory()->create();
    $blogPl = Blog::factory()->create(['user_id' => $user->id, 'locale' => 'pl', 'is_published' => true]);
    $blogEn = Blog::factory()->create(['user_id' => $user->id, 'locale' => 'en', 'is_published' => true]);

    // Guest visiting Polish blog
    $this->get("/{$blogPl->slug}")
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));

    // Guest visiting English blog
    $this->get("/{$blogEn->slug}")
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'en'));

    // Authenticated user (with 'en' setting) visiting Polish blog - blog locale should win
    $authUser = User::factory()->create(['locale' => 'en']);
    $this->actingAs($authUser)
        ->get("/{$blogPl->slug}")
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));
});

it('sets locale based on blog setting for newsletter subscribe page', function () {
    $user = User::factory()->create();
    $blogPl = Blog::factory()->create(['user_id' => $user->id, 'locale' => 'pl', 'is_published' => true]);

    $this->get(route('newsletter.index', ['blog_id' => $blogPl->id]))
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));
});

it('sets locale based on blog setting for newsletter manage page', function () {
    $user = User::factory()->create();
    $blogPl = Blog::factory()->create(['user_id' => $user->id, 'locale' => 'pl', 'is_published' => true]);
    $email = 'test@example.com';
    NewsletterSubscription::factory()->create([
        'email' => $email,
        'blog_id' => $blogPl->id
    ]);

    $url = URL::signedRoute('newsletter.manage', ['email' => $email]);

    $this->get($url)
        ->assertInertia(fn(Assert $page) => $page->where('translations.locale', 'pl'));
});

it('updates user locale when changing it via appearance settings', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->post('/locale', ['locale' => 'pl'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('pl');
});
