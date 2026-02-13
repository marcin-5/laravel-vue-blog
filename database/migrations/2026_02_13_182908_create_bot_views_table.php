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
        Schema::create('bot_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_agent_id')->constrained()->cascadeOnDelete();
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['user_agent_id', 'viewable_type', 'viewable_id'], 'bot_views_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_views');
    }
};
