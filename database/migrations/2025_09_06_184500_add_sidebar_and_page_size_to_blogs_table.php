<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->integer('sidebar')->default(0)->comment('0 = no sidebar; <0 left with |value|%; >0 right with value%')->after('locale');
            $table->unsignedSmallInteger('page_size')->default(10)->after('sidebar');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['sidebar', 'page_size']);
        });
    }
};
