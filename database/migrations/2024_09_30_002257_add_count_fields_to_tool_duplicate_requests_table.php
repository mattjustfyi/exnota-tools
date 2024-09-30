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
            $table->after('duplicates', function (Blueprint $table) {
                $table->integer('duplicates_count')->nullable();
            });
            $table->after('delete_list', function (Blueprint $table) {
                $table->integer('delete_list_count')->nullable();
            });
            $table->after('delete_results', function (Blueprint $table) {
                $table->integer('delete_results_count')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropColumn('duplicates_count');
            $table->dropColumn('delete_list_count');
            $table->dropColumn('delete_results_count');
        });
    }
};
