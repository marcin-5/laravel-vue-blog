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
            ->loadDeferredProps(fn(Assert $page) => $page
                ->where('newsletterSubscriptions', [])
                ->where('blogStats', [])
                ->where('postsStats', [])
                ->where('userAgentStats', null)
                ->where('botStats', null)
                ->where('fontUsageStats', null)
            )
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
            ->loadDeferredProps(fn(Assert $page) => $page
                ->has('blogStats', 1)
                ->where('blogStats.0.name', 'Test Blog')
                ->has('postsStats.timeline')
                ->has('postsStats.performance')
                ->where('newsletterSubscriptions', [])
                ->where('userAgentStats', null)
                ->where('botStats', null)
                ->where('fontUsageStats', null)
            )
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
            ->loadDeferredProps(fn(Assert $page) => $page
                ->has('newsletterSubscriptions', 1)
                ->where('newsletterSubscriptions.0.email', 'subscriber@example.com')
                ->has('userAgentStats')
                ->has('botStats')
                ->has('fontUsageStats.fonts', 19)
                ->where('blogStats', [])
                ->where('postsStats', [])
            )
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
            ->loadDeferredProps(fn(Assert $page) => $page
                ->has('newsletterSubscriptions')
                ->has('blogStats')
                ->has('postsStats')
                ->has('userAgentStats')
                ->has('botStats')
            )
            ->has('translations')
        );
});

test('admin font usage stats include all bloggers and every catalog font by default', function () {
    $admin = User::factory()->create(['role' => 'user']);
    $admin->givePermissionTo('view_admin_stats');

    $firstBlogger = User::factory()->create(['role' => 'blogger', 'name' => 'First Blogger']);
    $secondBlogger = User::factory()->create(['role' => 'blogger', 'name' => 'Second Blogger']);

    $expectedBloggers = User::query()
        ->whereIn('role', ['admin', 'blogger'])
        ->select(['id', 'name'])
        ->orderBy('name')
        ->get()
        ->map(fn(User $user): array => ['id' => $user->id, 'name' => $user->name])
        ->all();

    createBlog([
        'user_id' => $firstBlogger->id,
        'name' => 'First Blogger Blog',
        'theme' => [
            'light' => [
                '--font-header' => 'var(--font-inter)',
                '--font-body' => 'inherit',
            ],
            'dark' => [
                '--font-header' => 'var(--font-inter)',
                '--font-body' => 'var(--font-bitter)',
            ],
        ],
    ]);
    createBlog([
        'user_id' => $secondBlogger->id,
        'name' => 'Second Blogger Blog',
        'theme' => ['dark' => ['--font-body' => 'var(--font-inter)']],
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->loadDeferredProps(fn(Assert $page) => $page
                ->where('fontUsageStats.selectedBloggerId', null)
                ->where('fontUsageStats.bloggers', $expectedBloggers)
                ->has('fontUsageStats.fonts', 19)
                ->where('fontUsageStats.fonts.4.value', 'var(--font-inter)')
                ->where('fontUsageStats.fonts.4.count', 2)
                ->where('fontUsageStats.fonts.4.blogs', ['First Blogger Blog', 'Second Blogger Blog'])
                ->where('fontUsageStats.fonts.12.count', 1)
                ->where('fontUsageStats.fonts.12.blogs', ['First Blogger Blog'])
                ->where('fontUsageStats.fonts.1.count', 0)
                ->where('fontUsageStats.fonts.1.blogs', [])
            )
        );
});

test('admin font usage stats filter blogs by the selected blogger', function () {
    $admin = User::factory()->create(['role' => 'user']);
    $admin->givePermissionTo('view_admin_stats');

    $selectedBlogger = User::factory()->create(['role' => 'blogger', 'name' => 'Selected Blogger']);
    $otherBlogger = User::factory()->create(['role' => 'blogger', 'name' => 'Other Blogger']);

    createBlog([
        'user_id' => $selectedBlogger->id,
        'name' => 'Selected Blog',
        'theme' => ['light' => ['--font-header' => 'var(--font-roboto)']],
    ]);
    createBlog([
        'user_id' => $otherBlogger->id,
        'name' => 'Other Blog',
        'theme' => ['light' => ['--font-header' => 'var(--font-bitter)']],
    ]);

    $this->actingAs($admin)
        ->get('/dashboard?blogger_id=' . $selectedBlogger->id)
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->loadDeferredProps(fn(Assert $page) => $page
                ->where('fontUsageStats.selectedBloggerId', $selectedBlogger->id)
                ->where('fontUsageStats.fonts.10.value', 'var(--font-roboto)')
                ->where('fontUsageStats.fonts.10.count', 1)
                ->where('fontUsageStats.fonts.10.blogs', ['Selected Blog'])
                ->where('fontUsageStats.fonts.12.count', 0)
            )
        );
});

test('admin font usage filter rejects users who are not bloggers', function () {
    $admin = User::factory()->create(['role' => 'user']);
    $admin->givePermissionTo('view_admin_stats');
    $regularUser = User::factory()->create();

    $this->actingAs($admin)
        ->get('/dashboard?blogger_id=' . $regularUser->id)
        ->assertSessionHasErrors('blogger_id');
});
