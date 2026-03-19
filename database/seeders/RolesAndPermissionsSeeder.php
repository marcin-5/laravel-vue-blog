<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
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

        // Create roles and assign existing permissions

        // Admin
        $adminRole = Role::findOrCreate(UserRole::Admin->value);
        $adminRole->givePermissionTo(Permission::all());

        // Blogger
        $bloggerRole = Role::findOrCreate(UserRole::Blogger->value);
        $bloggerRole->givePermissionTo([
            'view_blogs',
            'view_blogger_stats',
            'manage_groups',
            'contribute_groups',
        ]);

        // Regular User
        Role::findOrCreate(UserRole::User->value);
    }
}
