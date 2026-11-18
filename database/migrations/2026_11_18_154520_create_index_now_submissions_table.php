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
        Schema::create('index_now_submissions', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->unsignedBigInteger('blog_id')->nullable()->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->boolean('succeeded');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('index_now_submissions');
    }
};
