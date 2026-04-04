<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStatsFilterVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_bloggers_list_and_filters_are_correctly_configured()
    {
        // Clean the database to be sure about the number of users
        User::query()->delete();

        $admin = User::factory()->create(['role' => UserRole::Admin->value, 'name' => 'Admin User']);
        $blogger = User::factory()->create(['role' => UserRole::Blogger->value, 'name' => 'Test Blogger']);

        $this
            ->actingAs($admin)
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/admin/Stats')
                ->has('bloggers', 2)
                ->where('bloggers.0.name', 'Admin User')
                ->where('bloggers.1.name', 'Test Blogger'),
            );
    }
}
