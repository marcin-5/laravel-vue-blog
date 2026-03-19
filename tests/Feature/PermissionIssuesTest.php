<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionIssuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_without_roles_table_entry_is_not_recognized_as_admin_when_tables_exist()
    {
        // Disable sync temporarily to create user "the old way"
        Config::set('permission.disabled', true);

        // 1. Create an admin user before Spatie roles are created
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'email' => 'admin@example.com',
        ]);

        // Enable sync and create role (simulate state after package migration)
        Config::set('permission.disabled', false);
        User::shouldSyncPermissions(true); // Reset cache
        Role::findOrCreate(UserRole::Admin->value);

        $admin->refresh();

        // 3. Verify the issue: shouldSyncPermissions() returns true, but user has no role in Spatie
        $this->assertTrue(User::shouldSyncPermissions());
        $this->assertFalse(
            $admin->hasRole(UserRole::Admin->value),
            'User should not have Spatie role without sync',
        );
        $this->assertFalse($admin->isAdmin(), 'Admin user should not be recognized as admin!');

        // 4. RUN REPAIR MIGRATION
        $migration = require database_path('migrations/2026_03_19_180631_sync_roles_for_existing_users.php');
        $migration->up();

        $admin->refresh();

        // 5. Now they should be an admin
        $this->assertTrue($admin->hasRole(UserRole::Admin->value));
        $this->assertTrue($admin->isAdmin());
    }
}
