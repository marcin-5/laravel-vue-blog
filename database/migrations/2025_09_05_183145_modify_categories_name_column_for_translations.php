<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL we must provide a USING clause when changing to json
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE categories ALTER COLUMN name TYPE json USING CASE WHEN name IS NULL OR name = '' THEN '{}'::json ELSE json_build_object('en', name) END");
            DB::statement("ALTER TABLE categories ALTER COLUMN name SET NOT NULL");
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->json('name')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Revert json translations to plain string by taking 'en' value, defaulting to empty string
            DB::statement("ALTER TABLE categories ALTER COLUMN name TYPE varchar(255) USING COALESCE((name->>'en'), '')");
            DB::statement("ALTER TABLE categories ALTER COLUMN name SET NOT NULL");
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('name')->change();
            });
        }
    }
};
