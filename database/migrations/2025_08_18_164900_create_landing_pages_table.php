<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->longText('content');
            $table->enum('sidebar_position', ['left', 'right', 'none'])->default('none');
            $table->timestamps();

            $table->unique('blog_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
