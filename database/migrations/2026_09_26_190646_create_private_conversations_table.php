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
        Schema::create('private_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->timestamps();

            $table->index(['owner_id', 'updated_at']);
            $table->index(['initiator_id', 'updated_at']);
            $table->index(['blog_id', 'updated_at']);
            $table->index(['group_id', 'updated_at']);
            $table->index(['subject', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_conversations');
    }
};
