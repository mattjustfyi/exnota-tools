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
        Schema::create('spotify_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique();
            $table->string('notion_id')->nullable();
            $table->foreignId('spotify_dashboard_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('duration_ms');
            $table->json('spotify_artist_ids');
            $table->string('spotify_album_id');
            $table->integer('popularity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotify_tracks');
    }
};
