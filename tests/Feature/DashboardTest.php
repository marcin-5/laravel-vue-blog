<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Ensure permissions exist
    Permission::findOrCreate('view_admin_stats');
    Permission::findOrCreate('view_blogs');
});

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users without permissions see empty stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/Dashboard')
            ->where('newsletterSubscriptions', [])
            ->where('blogStats', [])
            ->where('postsStats', [])
            ->where('userAgentStats', null)
            ->where('botStats', null)
        );
});

test('authenticated users with view_blogs permission see blog stats', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_blogs');
    $this->actingAs($user);

    $blog = createBlog(['user_id' => $user->id, 'name' => 'Test Blog']);
    createPost($blog, ['title' => 'Test Post']);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/Dashboard')
            ->has('blogStats', 1)
            ->where('blogStats.0.name', 'Test Blog')
            ->has('postsStats.timeline')
            ->has('postsStats.performance')
            ->where('newsletterSubscriptions', [])
            ->where('userAgentStats', null)
            ->where('botStats', null)
        );
});

test('authenticated users with view_admin_stats permission see admin stats', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_admin_stats');
    $this->actingAs($user);

    $blog = createBlog(['name' => 'Other Blog']);
    createSubscription($blog, ['email' => 'subscriber@example.com']);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/Dashboard')
            ->has('newsletterSubscriptions', 1)
            ->where('newsletterSubscriptions.0.email', 'subscriber@example.com')
            ->has('userAgentStats')
            ->has('botStats')
            ->where('blogStats', [])
            ->where('postsStats', [])
        );
});

test('authenticated users with all permissions see all stats', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_admin_stats', 'view_blogs']);
    $this->actingAs($user);

    $blog = createBlog(['user_id' => $user->id, 'name' => 'My Blog']);
    createSubscription($blog, ['email' => 'subscriber@example.com']);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/Dashboard')
            ->has('newsletterSubscriptions')
            ->has('blogStats')
            ->has('postsStats')
            ->has('userAgentStats')
            ->has('botStats')
            ->has('translations')
        );
});
