<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tool_duplicate_requests')->update([
            'duplicates' => DB::raw("JSON_ARRAY_MAP(
                duplicates,
                (entry) -> JSON_REMOVE(
                    JSON_REMOVE(
                        entry,
                        '$.keep.property_values'
                    ),
                    '$.delete[*].property_values'
                )
            )")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Since we're removing data, we can't restore it in the down migration.
        // We'll add a comment to explain this.
        DB::statement("
            -- The 'property_values' keys were removed from the 'duplicates' column.
            -- This data cannot be automatically restored.
        ");
    }
};
