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
        Schema::table('private_messages', function (Blueprint $table) {
            $table->unsignedInteger('notification_version')->default(1)->after('content');
            $table->unsignedInteger('notification_sent_version')->nullable()->after('notification_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropColumn(['notification_version', 'notification_sent_version']);
        });
    }
};
