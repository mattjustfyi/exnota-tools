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
        Schema::table('spotify_tracks', function (Blueprint $table) {
            $table->dropUnique(['spotify_id']);
            $table->unique(['spotify_dashboard_id', 'spotify_id']);
        });

        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->dropUnique(['spotify_id']);
            $table->unique(['spotify_dashboard_id', 'spotify_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_tracks', function (Blueprint $table) {
            $table->dropUnique(['spotify_dashboard_id', 'spotify_id']);
            $table->unique(['spotify_id']);
        });

        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->dropUnique(['spotify_dashboard_id', 'spotify_id']);
            $table->unique(['spotify_id']);
        });
    }
};
