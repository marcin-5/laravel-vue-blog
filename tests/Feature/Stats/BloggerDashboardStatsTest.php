<?php

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;

test('blogger sees their blog statistics', function () {
    $user = User::factory()->create(['role' => User::ROLE_BLOGGER]);
    $blog = Blog::factory()->create(['user_id' => $user->id]);

    // Create posts
    Post::factory()->count(3)->create(['blog_id' => $blog->id, 'published_at' => now()->subDays(10)]);

    // Create views for posts
    $post = Post::factory()->create(['blog_id' => $blog->id, 'published_at' => now()]);
    PageView::factory()->count(5)->create([
        'viewable_type' => (new Post)->getMorphClass(),
        'viewable_id' => $post->id,
    ]);

    // Create subscriptions
    NewsletterSubscription::factory()->count(2)->create([
        'blog_id' => $blog->id,
        'frequency' => 'daily',
    ]);
    NewsletterSubscription::factory()->count(3)->create([
        'blog_id' => $blog->id,
        'frequency' => 'weekly',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->has('blogStats', 1)
        ->where('blogStats.0.name', $blog->name)
        ->where('blogStats.0.posts_count', 4)
        ->where('blogStats.0.lifetime_views', 5)
        ->where('blogStats.0.daily_subscriptions_count', 2)
        ->where('blogStats.0.weekly_subscriptions_count', 3)
        ->has('postsStats.timeline')
        ->has('postsStats.performance')
        ->where('postsStats.timeline.0.title', $post->title)
        ->where('postsStats.timeline.0.views.total', 5)
        ->where('postsStats.performance.0.title', $post->title),
    );
});
