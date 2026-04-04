<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStatsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_stats_by_default_even_without_own_blogs()
    {
        // 1. Create an admin who has NO blogs
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        // 2. Create another blogger who HAS a blog and a post with views
        $blogger = User::factory()->create(['role' => UserRole::Blogger->value]);
        $blog = Blog::factory()->create(['user_id' => $blogger->id, 'name' => 'Other Blogger Blog']);
        $post = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Other Blogger Post']);

        $ua = UserAgent::factory()->create(['name' => 'Mozilla/5.0']);

        // Add a view to the blog
        PageView::factory()->create([
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'created_at' => now(),
            'user_agent' => 'Mozilla/5.0',
            'user_agent_id' => $ua->id,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess1',
            'visitor_id' => 'v1',
        ]);

        // Add a view to the post
        PageView::factory()->create([
            'viewable_type' => $post->getMorphClass(),
            'viewable_id' => $post->id,
            'created_at' => now(),
            'user_agent' => 'Mozilla/5.0',
            'user_agent_id' => $ua->id,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess1',
            'visitor_id' => 'v1',
        ]);

        // 3. Act as admin and visit blogger stats page with user's parameters
        $url = route('blogger.stats.index', [
            'posts_range' => 'lifetime',
            'posts_size' => 5,
            'posts_sort' => 'views_desc',
            'range' => 'lifetime',
            'size' => 5,
            'sort' => 'views_desc',
            'special_visitors_group_by' => 'visitor_id',
            'special_visitors_range' => 'lifetime',
            'special_visitors_size' => 5,
            'special_visitors_sort' => 'views_desc',
            'special_visitors_type' => 'all',
            'visitors_group_by' => 'visitor_id',
            'visitors_range' => 'lifetime',
            'visitors_size' => 5,
            'visitors_sort' => 'views_desc',
            'visitors_type' => 'all',
        ]);
        $response = $this->actingAs($admin)->get($url);

        // 4. Assert that admin sees the other blogger's stats on the blogger page
        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/Stats')
            ->has('blogs', 1)
            ->where('blogs.0.name', 'Other Blogger Blog')
            ->has('posts', 1)
            ->where('posts.0.title', 'Other Blogger Post')
            ->has('visitorsFromPage', 1),
        );
    }
}
