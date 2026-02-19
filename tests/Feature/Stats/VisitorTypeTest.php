<?php

namespace Tests\Feature\Stats;

use App\Models\Blog;
use App\Models\AnonymousView;
use App\Models\BotView;
use App\Models\PageView;
use App\Models\User;
use App\Models\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VisitorTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_visitors_by_bots()
    {
        $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
        $blog = Blog::factory()->create(['user_id' => $blogger->id]);
        $ua = UserAgent::factory()->create(['name' => 'Googlebot']);

        BotView::factory()->create([
            'user_agent_id' => $ua->id,
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'hits' => 10,
        ]);

        // Regular human view
        PageView::factory()->create([
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.stats.index', ['special_visitors_type' => 'bots']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('visitorsFromSpecial', 1)
                ->where('visitorsFromSpecial.0.visitor_label', 'Googlebot')
                ->where('visitorsFromSpecial.0.blog_views', 10),
            );
    }

    public function test_can_filter_visitors_by_anonymous()
    {
        $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
        $blog = Blog::factory()->create(['user_id' => $blogger->id]);

        // Anonymous view (anonymous_views table, grouped by user agent)
        $ua = UserAgent::factory()->create(['name' => 'Mozilla/Anonymous']);
        AnonymousView::factory()->create([
            'user_agent_id' => $ua->id,
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'hits' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.stats.index', ['special_visitors_type' => 'anonymous']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('visitorsFromSpecial', 1)
                ->where('visitorsFromSpecial.0.visitor_label', 'Mozilla/Anonymous'),
            );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
