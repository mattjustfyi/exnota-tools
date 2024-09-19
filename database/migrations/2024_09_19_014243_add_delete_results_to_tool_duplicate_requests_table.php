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
            $table->json('delete_results')->nullable()->after('duplicates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropColumn('delete_results');
        });
    }
};
