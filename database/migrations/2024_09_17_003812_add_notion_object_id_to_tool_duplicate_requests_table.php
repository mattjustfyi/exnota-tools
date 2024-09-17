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
            $table->unsignedBigInteger('notion_object_id')->nullable();
            $table->foreign('notion_object_id')->references('id')->on('notion_objects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropForeign(['notion_object_id']);
            $table->dropColumn('notion_object_id');
        });
    }
};
