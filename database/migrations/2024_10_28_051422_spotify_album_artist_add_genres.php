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
        Schema::table('spotify_albums', function (Blueprint $table) {
            $table->jsonb('genres')->nullable();
        });

        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->jsonb('genres')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_albums', function (Blueprint $table) {
            $table->dropColumn('genres');
        });

        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->dropColumn('genres');
        });
    }
};
