<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_visibility_check");
            DB::statement("ALTER TABLE posts ALTER COLUMN visibility TYPE VARCHAR(255)");
            DB::statement(
                "ALTER TABLE posts ADD CONSTRAINT posts_visibility_check CHECK (visibility IN ('public', 'registered', 'unlisted'))",
            );
        } else {
            Schema::table('posts', function (Blueprint $table) {
                $table->enum('visibility', ['public', 'registered', 'unlisted'])->default('public')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_visibility_check");
            DB::statement("ALTER TABLE posts ALTER COLUMN visibility TYPE VARCHAR(255)");
            DB::statement(
                "ALTER TABLE posts ADD CONSTRAINT posts_visibility_check CHECK (visibility IN ('public', 'registered'))",
            );
        } else {
            Schema::table('posts', function (Blueprint $table) {
                $table->enum('visibility', ['public', 'registered'])->default('public')->change();
            });
        }
    }
};
