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
            // Change column types to jsonb
            $table->jsonb('duplicates')->nullable(true)->change();
            $table->jsonb('delete_results')->nullable(true)->change();
            $table->jsonb('delete_list')->nullable(true)->change();
            $table->jsonb('delete_retry_list')->nullable(true)->change();
            $table->jsonb('search_results')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->json('duplicates')->nullable()->change();
            $table->json('delete_results')->nullable()->change();
            $table->json('delete_list')->nullable()->change();
            $table->json('delete_retry_list')->nullable()->change();
            $table->json('search_results')->nullable()->change();
        });
    }
};
