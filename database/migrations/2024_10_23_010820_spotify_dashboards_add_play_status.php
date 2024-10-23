<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spotify_dashboards', function (Blueprint $table) {
            $table->string('play_status')->nullable();
            $table->jsonb('current_track')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_dashboards', function (Blueprint $table) {
            $table->dropColumn(['play_status', 'current_track']);
        });
    }
};
