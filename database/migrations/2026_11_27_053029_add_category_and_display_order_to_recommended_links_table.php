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
        Schema::table('recommended_links', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('blog_id')
                ->constrained('recommended_link_categories')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0)->after('category_id');
            $table->index(['blog_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_links', function (Blueprint $table) {
            $table->dropIndex(['blog_id', 'display_order']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('display_order');
        });
    }
};
