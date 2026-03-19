<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Ensure all permissions exist
        $permissions = [
            'view_admin_users',
            'view_admin_categories',
            'view_admin_stats',
            'view_blogs',
            'view_blogger_stats',
            'manage_groups',
            'contribute_groups',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // 2. Ensure roles exist and assign permissions
        $adminRole = Role::findOrCreate(UserRole::Admin->value);
        $adminRole->syncPermissions(Permission::all());

        $bloggerRole = Role::findOrCreate(UserRole::Blogger->value);
        $bloggerRole->syncPermissions([
            'view_blogs',
            'view_blogger_stats',
            'manage_groups',
            'contribute_groups',
        ]);

        Role::findOrCreate(UserRole::User->value);

        // 3. Ensure all existing users are synced to their roles (idempotent)
        User::query()->each(function (User $user) {
            if ($user->role) {
                try {
                    $user->syncRoles([$user->role]);
                } catch (Throwable) {
                    // Ignore errors during migration
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down required, it's safe to keep permissions/roles
    }
};
