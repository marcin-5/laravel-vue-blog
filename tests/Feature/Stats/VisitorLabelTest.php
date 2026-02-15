<?php

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('visitor stats displays user name if available, newsletter email otherwise, or visitor_id fallback', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->actingAs($admin);

    $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
    $blog = Blog::factory()->create(['user_id' => $blogger->id]);

    $now = now();

    // Case 1: Logged user - shows name
    $loggedUser = User::factory()->create(['name' => 'Logged User']);
    PageView::factory()->create([
        'user_id' => $loggedUser->id,
        'visitor_id' => Str::uuid(),
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'created_at' => $now->copy()->subDays(7),
    ]);

    // Case 2: Newsletter subscriber - shows email
    $visitorIdNews = Str::uuid();
    NewsletterSubscription::factory()->create([
        'visitor_id' => $visitorIdNews,
        'email' => 'sub@example.com',
        'blog_id' => $blog->id,
        'frequency' => 'weekly',
    ]);
    PageView::factory()->create([
        'visitor_id' => $visitorIdNews,
        'user_id' => null,
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'created_at' => $now->copy()->subDays(7),
    ]);

    // Case 3: Anonymous no sub - shows visitor_id
    $visitorIdAnon = Str::uuid();
    PageView::factory()->create([
        'visitor_id' => $visitorIdAnon,
        'user_id' => null,
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'created_at' => $now->copy()->subDays(7),
    ]);

    $response = $this->get(route('admin.stats.index') . '?visitors_limit=100&visitors_group_by=visitor_id');

    $response->assertInertia(
        fn($page) => $page
            ->component('app/admin/Stats')
            ->where(
                'visitorsFromPage',
                fn($visitors) => collect($visitors)->count() === 3 && collect($visitors)->pluck(
                        'visitor_label',
                    )->contains('Logged User') && collect($visitors)->pluck('visitor_label')->contains(
                        'sub@example.com',
                    ) && collect($visitors)->pluck('visitor_label')->contains($visitorIdAnon),
            ),
    );
});
