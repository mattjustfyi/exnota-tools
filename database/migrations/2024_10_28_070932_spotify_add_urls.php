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
        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->string('url')->nullable();
        });

        Schema::table('spotify_albums', function (Blueprint $table) {
            $table->string('url')->nullable();
        });

        Schema::table('spotify_tracks', function (Blueprint $table) {
            $table->string('url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->dropColumn('url');
        });

        Schema::table('spotify_albums', function (Blueprint $table) {
            $table->dropColumn('url');
        });

        Schema::table('spotify_tracks', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
