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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->text('footer')->nullable();
            $table->json('theme')->nullable();
            $table->integer('sidebar')->default(0);
            $table->integer('page_size')->default(15);
            $table->boolean('is_published')->default(true);
            $table->string('locale', 5)->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
