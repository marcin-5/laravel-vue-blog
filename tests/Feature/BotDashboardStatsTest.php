<?php

namespace Tests\Feature;

use App\Models\BotView;
use App\Models\User;
use App\Models\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BotDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_bot_stats_on_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ua1 = UserAgent::factory()->create(
            ['name' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
        );
        $ua2 = UserAgent::factory()->create(['name' => 'UnknownCrawler/1.0']);

        BotView::factory()->create([
            'user_agent_id' => $ua1->id,
            'hits' => 10,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        BotView::factory()->create([
            'user_agent_id' => $ua2->id,
            'hits' => 50,
            'last_seen_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/Dashboard')
                ->has('botStats')
                ->where('botStats.total_hits', 60)
                ->has('botStats.last_seen', 2)
                ->where('botStats.last_seen.0.name', 'UnknownCrawler/1.0')
                ->where('botStats.last_seen.0.matched_fragment', 'UnknownCrawler/1.0')
                ->where(
                    'botStats.last_seen.1.name',
                    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                )
                ->where('botStats.last_seen.1.matched_fragment', 'googlebot') // Found in config/bots.php
                ->has('botStats.top_hits', 2)
                ->where('botStats.top_hits.0.name', 'UnknownCrawler/1.0')
                ->where('botStats.top_hits.0.hits', 50),
            );
    }

    public function test_blogger_cannot_see_bot_stats_on_dashboard(): void
    {
        $blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);

        $this->actingAs($blogger)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/Dashboard')
                ->where('botStats', null),
            );
    }
}
