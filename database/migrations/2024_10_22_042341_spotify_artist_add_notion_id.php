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
            $table->string('notion_id')->nullable()->after('spotify_dashboard_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotify_artists', function (Blueprint $table) {
            $table->dropColumn('notion_id');
        });
    }
};
