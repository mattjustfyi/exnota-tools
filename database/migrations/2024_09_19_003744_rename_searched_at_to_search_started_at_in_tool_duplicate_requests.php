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
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->renameColumn('searched_at', 'search_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->renameColumn('search_started_at', 'searched_at');
        });
    }
};
