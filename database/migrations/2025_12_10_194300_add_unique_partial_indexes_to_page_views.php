<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Only apply on PostgreSQL where partial indexes are supported in this form
        if (DB::getDriverName() === 'pgsql') {
            // 1) Clean up historical duplicates so unique indexes can be created safely.
            // Keep the oldest record per unique key and delete the rest.
            // a) Per (user_id, viewable_type, viewable_id)
            DB::statement(
                "DELETE FROM page_views a USING page_views b\n" .
                "WHERE a.user_id IS NOT NULL\n" .
                "AND b.user_id = a.user_id\n" .
                "AND b.viewable_type = a.viewable_type\n" .
                "AND b.viewable_id = a.viewable_id\n" .
                "AND b.id < a.id",
            );

            // b) Per (visitor_id, viewable_type, viewable_id) but only for anonymous rows (user_id IS NULL)
            DB::statement(
                "DELETE FROM page_views a USING page_views b\n" .
                "WHERE a.user_id IS NULL AND a.visitor_id IS NOT NULL\n" .
                "AND b.user_id IS NULL AND b.visitor_id = a.visitor_id\n" .
                "AND b.viewable_type = a.viewable_type\n" .
                "AND b.viewable_id = a.viewable_id\n" .
                "AND b.id < a.id",
            );

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS page_views_unique_user_viewable
                ON page_views (user_id, viewable_type, viewable_id)
                WHERE user_id IS NOT NULL',
            );

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS page_views_unique_visitor_viewable
                ON page_views (visitor_id, viewable_type, viewable_id)
                WHERE visitor_id IS NOT NULL AND user_id IS NULL',
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS page_views_unique_user_viewable');
            DB::statement('DROP INDEX IF EXISTS page_views_unique_visitor_viewable');
        }
    }
};
