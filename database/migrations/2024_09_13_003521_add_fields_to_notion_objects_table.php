<?php

use App\Enums\NotionObjectType;
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
        Schema::table('notion_objects', function (Blueprint $table) {
            $table->enum('type', array_column(NotionObjectType::cases(), 'value'))->after('id');
            $table->string('name')->after('type');
            $table->json('data')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notion_objects', function (Blueprint $table) {
            $table->dropColumn(['type', 'name', 'data']);
        });
    }
};
