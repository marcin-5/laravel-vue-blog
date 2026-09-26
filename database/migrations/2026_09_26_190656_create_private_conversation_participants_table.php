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
        Schema::create('private_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_notifications')->default(true);
            $table->timestamps();

            $table->unique(['private_conversation_id', 'user_id']);
            $table->index(['user_id', 'email_notifications']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_conversation_participants');
    }
};
