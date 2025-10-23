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
        // First, convert any integer values to boolean
        DB::statement("UPDATE class_models SET is_active = CASE WHEN is_active::text = '1' OR is_active::text = 't' OR is_active::text = 'true' THEN true ELSE false END WHERE is_active IS NOT NULL");
        
        // Drop and recreate the column as boolean with default
        DB::statement('ALTER TABLE class_models DROP COLUMN IF EXISTS is_active');
        DB::statement('ALTER TABLE class_models ADD COLUMN is_active boolean NOT NULL DEFAULT true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this fix
    }
};
