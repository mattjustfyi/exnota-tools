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
        Schema::table('spotify_play_histories', function (Blueprint $table) {
            $table->string('notion_id')->nullable();
            $table->foreignId('spotify_dashboard_id')->constrained('spotify_dashboards');
            $table->string('spotify_track_id');
            $table->timestamp('played_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_play_histories', function (Blueprint $table) {
            $table->dropColumn('notion_id');
            $table->dropForeign(['spotify_dashboard_id']);
            $table->dropColumn('spotify_dashboard_id');
            $table->dropColumn('spotify_track_id');
            $table->dropColumn('played_at');
        });
    }
};
