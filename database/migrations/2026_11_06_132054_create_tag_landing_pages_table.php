<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tag_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->timestamps();

            $table->unique('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_landing_pages');
    }
};
