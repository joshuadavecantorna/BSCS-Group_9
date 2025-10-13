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
    // Fix any data type mismatches in the students table
    // 1. Force is_active to boolean for all rows
    DB::statement("UPDATE students SET is_active = CASE WHEN is_active::text IN ('1', 't', 'true', 'TRUE', 'yes', 'YES') THEN 'true' ELSE 'false' END WHERE is_active IS NOT NULL");
    DB::statement('ALTER TABLE students ALTER COLUMN is_active TYPE boolean USING is_active::boolean');
    DB::statement('ALTER TABLE students ALTER COLUMN is_active SET DEFAULT true');
    DB::statement('ALTER TABLE students ALTER COLUMN is_active SET NOT NULL');

    // 2. Force created_at and updated_at to proper timestamp for all rows
    DB::statement("UPDATE students SET created_at = CASE WHEN created_at IS NULL OR created_at = '' THEN NOW() ELSE created_at END");
    DB::statement("UPDATE students SET updated_at = CASE WHEN updated_at IS NULL OR updated_at = '' THEN NOW() ELSE updated_at END");
    DB::statement('ALTER TABLE students ALTER COLUMN created_at TYPE timestamp USING created_at::timestamp');
    DB::statement('ALTER TABLE students ALTER COLUMN updated_at TYPE timestamp USING updated_at::timestamp');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as we're just ensuring correct types
    }
};
