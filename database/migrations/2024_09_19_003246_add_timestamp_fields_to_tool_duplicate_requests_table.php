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
            $table->timestamp('search_ended_at')->nullable();
            $table->timestamp('delete_started_at')->nullable();
            $table->timestamp('delete_ended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropColumn('search_ended_at');
            $table->dropColumn('delete_started_at');
            $table->dropColumn('delete_ended_at');
        });
    }
};
