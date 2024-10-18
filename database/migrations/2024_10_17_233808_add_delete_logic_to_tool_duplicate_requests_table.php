<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DeleteLogic;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->enum('delete_logic', ['keep_oldest', 'keep_latest'])
                ->after('search_ended_at');
        });

        // Update existing records to 'keep_oldest'
        DB::table('tool_duplicate_requests')->update(['delete_logic' => 'keep_oldest']);

        // Set default for new records to 'keep_latest'
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->enum('delete_logic', ['keep_oldest', 'keep_latest'])
                ->default('keep_latest')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropColumn('delete_logic');
        });
    }
};
