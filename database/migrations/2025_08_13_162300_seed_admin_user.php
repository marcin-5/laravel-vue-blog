<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the users table exists before seeding
        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            return;
        }

        // Run the specific seeder to create/update the admin user
        // We avoid relying on CLI here and directly instantiate the seeder class for reliability.
        $seederClass = \Database\Seeders\AdminUserSeeder::class;
        (new $seederClass)->run();
    }

    public function down(): void
    {
        // Intentionally left blank. We don't automatically delete the admin user on rollback.
    }
};
