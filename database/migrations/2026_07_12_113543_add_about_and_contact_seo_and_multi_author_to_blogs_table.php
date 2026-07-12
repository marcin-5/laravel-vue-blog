<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('about_seo_description', 500)->nullable()->after('seo_description');
            $table->string('contact_seo_description', 500)->nullable()->after('about_seo_description');
            $table->boolean('is_multi_author')->default(false)->after('about');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['about_seo_description', 'contact_seo_description', 'is_multi_author']);
        });
    }
};
