<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default to 0 at DB level; model logic will set 1 for bloggers on create
            /** @noinspection UnknownColumnInspection */
            $table->unsignedInteger('blog_quota')->default(0)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blog_quota');
        });
    }
};
