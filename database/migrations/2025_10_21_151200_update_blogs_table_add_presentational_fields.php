<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Presentation / settings fields
            if (!Schema::hasColumn('blogs', 'motto')) {
                $table->text('motto')->nullable()->after('description');
            }
            if (!Schema::hasColumn('blogs', 'footer')) {
                $table->longText('footer')->nullable()->after('motto');
            }

            // Publication and locale
            if (!Schema::hasColumn('blogs', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('footer');
            }
            if (!Schema::hasColumn('blogs', 'locale')) {
                $table->string('locale', 10)->default(config('app.locale', 'en'))->after('is_published');
            }

            // Layout and pagination
            if (!Schema::hasColumn('blogs', 'sidebar')) {
                $table->integer('sidebar')->default(0)->after('locale');
            }
            if (!Schema::hasColumn('blogs', 'page_size')) {
                $table->unsignedSmallInteger('page_size')->default(10)->after('sidebar');
            }
        });

        // Helpful index for public queries (use IF NOT EXISTS for Postgres safety)
        DB::statement('CREATE INDEX IF NOT EXISTS blogs_is_published_index ON blogs (is_published)');
    }

    public function down(): void
    {
        // Drop index safely if it exists
        DB::statement('DROP INDEX IF EXISTS blogs_is_published_index');

        Schema::table('blogs', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('blogs', 'motto')) {
                $columns[] = 'motto';
            }
            if (Schema::hasColumn('blogs', 'footer')) {
                $columns[] = 'footer';
            }
            if (Schema::hasColumn('blogs', 'is_published')) {
                $columns[] = 'is_published';
            }
            if (Schema::hasColumn('blogs', 'locale')) {
                $columns[] = 'locale';
            }
            if (Schema::hasColumn('blogs', 'sidebar')) {
                $columns[] = 'sidebar';
            }
            if (Schema::hasColumn('blogs', 'page_size')) {
                $columns[] = 'page_size';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
