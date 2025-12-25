<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_post_stats_without_selecting_blog()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
        $blog = Blog::factory()->create(['user_id' => $blogger->id]);
        $post = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Top Post']);

        // Create view
        PageView::create([
            'viewable_type' => $post->getMorphClass(),
            'viewable_id' => $post->id,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess1',
            'user_agent' => 'test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('posts', 1)
                ->where('posts.0.title', 'Top Post')
                ->where('posts.0.views', 1),
            );
    }

    public function test_admin_can_filter_posts_independently()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
        $blog = Blog::factory()->create(['user_id' => $blogger->id]);
        $post = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Filtered Post']);

        // Create a view from 2 weeks ago
        $view = PageView::create([
            'viewable_type' => $post->getMorphClass(),
            'viewable_id' => $post->id,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess_old',
            'user_agent' => 'test',
        ]);
        $view->created_at = now()->subDays(10);
        $view->save();

        // Default request (range=week) -> should see 0 posts
        $this->actingAs($admin)
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('posts', 0),
            );

        // Request with posts_range=month -> should see the post
        $this->actingAs($admin)
            ->get(route('admin.stats.index', ['posts_range' => 'month']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('posts', 1)
                ->where('posts.0.title', 'Filtered Post')
                ->where('blogFilters.range', 'week')
                ->where('postFilters.range', 'month'),
            );
    }

    public function test_default_items_size_is_five_for_both_sections()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->where('blogFilters.size', 5)
                ->where('postFilters.size', 5),
            );
    }

    public function test_explicit_all_items_size_zero_is_preserved()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(
                route('admin.stats.index', [
                    'size' => 0,
                    'posts_size' => 0,
                ]),
            )
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->where('blogFilters.size', 0)
                ->where('postFilters.size', 0),
            );
    }
}
