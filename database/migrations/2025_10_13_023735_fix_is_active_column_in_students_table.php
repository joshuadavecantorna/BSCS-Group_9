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
        // First, update any non-boolean values to proper boolean values
        DB::statement("UPDATE students SET is_active = CASE WHEN CAST(is_active AS CHAR) = '1' OR CAST(is_active AS CHAR) = 't' OR CAST(is_active AS CHAR) = 'true' THEN 1 ELSE 0 END WHERE is_active IS NOT NULL");
        
        // Ensure the column is properly typed as boolean
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to integer if needed
        DB::statement('ALTER TABLE students ALTER COLUMN is_active TYPE integer USING (is_active::integer)');
        DB::statement('ALTER TABLE students ALTER COLUMN is_active SET DEFAULT 1');
    }
};
