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
        Schema::create('spotify_artists', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique()->index();
            $table->string('name');
            $table->foreignId('spotify_dashboard_id')->constrained('spotify_dashboards')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotify_artists');
    }
};
