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
            // Update null values to empty JSON arrays
            DB::statement("UPDATE tool_duplicate_requests SET duplicates = '[]' WHERE duplicates IS NULL");
            DB::statement("UPDATE tool_duplicate_requests SET delete_results = '[]' WHERE delete_results IS NULL");
            DB::statement("UPDATE tool_duplicate_requests SET delete_list = '[]' WHERE delete_list IS NULL");
            DB::statement("UPDATE tool_duplicate_requests SET delete_retry_list = '[]' WHERE delete_retry_list IS NULL");
            DB::statement("UPDATE tool_duplicate_requests SET search_results = '[]' WHERE search_results IS NULL");

            // Change column types to jsonb
            $table->jsonb('duplicates')->nullable(false)->change();
            $table->jsonb('delete_results')->nullable(false)->change();
            $table->jsonb('delete_list')->nullable(false)->change();
            $table->jsonb('delete_retry_list')->nullable(false)->change();
            $table->jsonb('search_results')->nullable(false)->change();
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
