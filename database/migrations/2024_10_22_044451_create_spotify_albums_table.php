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
        Schema::create('spotify_albums', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id');
            $table->string('name');
            $table->foreignId('spotify_dashboard_id')->constrained('spotify_dashboards');
            $table->string('notion_id')->nullable();
            $table->string('image_url')->nullable();
            $table->string('album_type')->nullable();
            $table->string('type')->nullable();
            $table->date('release_date')->nullable();
            $table->json('spotify_artist_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotify_albums');
    }
};
