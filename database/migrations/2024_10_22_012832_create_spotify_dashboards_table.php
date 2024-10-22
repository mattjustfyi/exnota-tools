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
        Schema::create('spotify_dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('artist_db_notion_id');
            $table->string('album_db_notion_id');
            $table->string('track_db_notion_id');
            $table->string('play_history_db_notion_id');
            $table->string('dashboard_page_notion_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotify_dashboards');
    }
};
