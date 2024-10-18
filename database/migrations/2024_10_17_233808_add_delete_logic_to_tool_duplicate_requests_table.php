<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DeleteLogic;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->enum('delete_logic', ['keep_oldest', 'keep_latest'])
                ->nullable(false)
                // Set existing records to 'keep_oldest'
                ->default('keep_latest')
                ->after('search_ended_at');
        });

        // Set existing records to 'keep_oldest' to represent what the previous behavior was
        DB::table('tool_duplicate_requests')
            ->update([
                "delete_logic" => "keep_oldest",
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 🚨 This down migration doesn't seem to delete the constraint on
        // values, so it may need to be manually removed.
        //
        // If I need to do this properly in the future, I might need a raw
        // statement like the one here: https://stackoverflow.com/a/25251270
        //
        // This is the statement DBeaver generated:
        //  ALTER TABLE public.tool_duplicate_requests DROP CONSTRAINT tool_duplicate_requests_delete_logic_check;
        Schema::table('tool_duplicate_requests', function (Blueprint $table) {
            $table->dropColumn('delete_logic');
        });
    }
};
