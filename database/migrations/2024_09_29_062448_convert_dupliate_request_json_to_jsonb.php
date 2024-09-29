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
            $table->jsonb('duplicates')->change();
            $table->jsonb('delete_results')->change();
            $table->jsonb('delete_list')->change();
            $table->jsonb('delete_retry_list')->change();
            $table->jsonb('search_results')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->json('duplicates')->change();
            $table->json('delete_results')->change();
            $table->json('delete_list')->change();
            $table->json('delete_retry_list')->change();
            $table->json('search_results')->change();
        });
    }
};
