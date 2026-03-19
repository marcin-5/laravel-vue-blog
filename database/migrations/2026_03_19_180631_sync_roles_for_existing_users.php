<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure all roles exist
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }

        // Sync all existing users' roles
        User::query()->each(function (User $user) {
            if ($user->role) {
                try {
                    $user->syncRoles([$user->role]);
                } catch (Throwable) {
                    // Ignore errors during migration if any
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't remove assignments in down, as it's safe to keep them
    }
};
