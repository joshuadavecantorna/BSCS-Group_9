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
        // For PostgreSQL, we need to ensure the column is properly cast
        DB::statement('ALTER TABLE teachers ALTER COLUMN is_active TYPE boolean USING is_active::boolean');
        DB::statement('ALTER TABLE teachers ALTER COLUMN is_active SET DEFAULT true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback changes
        DB::statement('ALTER TABLE teachers ALTER COLUMN is_active TYPE boolean USING is_active::boolean');
    }
};
