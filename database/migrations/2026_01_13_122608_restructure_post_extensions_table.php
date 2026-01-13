<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Usuń starą tabelę post_extensions
        Schema::dropIfExists('post_extensions');

        // 2. Utwórz nową tabelę pivot
        Schema::create('post_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('extension_post_id')
                ->constrained('posts')
                ->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            // Unikalność - to samo rozszerzenie nie może być dwa razy w tym samym poście
            $table->unique(['post_id', 'extension_post_id']);
        });

        // 3. Aktualizuj CHECK constraint dla visibility w tabeli posts
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_visibility_check");
            DB::statement(
                "ALTER TABLE posts ADD CONSTRAINT posts_visibility_check CHECK (visibility IN ('public', 'registered', 'unlisted', 'extension'))",
            );
        }
    }

    public function down(): void
    {
        // Przywróć oryginalny CHECK constraint
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_visibility_check");
            DB::statement(
                "ALTER TABLE posts ADD CONSTRAINT posts_visibility_check CHECK (visibility IN ('public', 'registered', 'unlisted'))",
            );
        }

        // Usuń nową tabelę pivot
        Schema::dropIfExists('post_extensions');

        // Przywróć oryginalną strukturę post_extensions
        Schema::create('post_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }
};
