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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('visitor_id', 64)
                ->nullable()
                ->index();

            $table->string('session_id', 128)
                ->nullable()
                ->index();

            $table->morphs('viewable');

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->string('fingerprint', 128)
                ->nullable()
                ->index();

            $table->timestamps();

            $table->index(['viewable_type', 'viewable_id', 'created_at'], 'page_views_viewable_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
