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
        // First, update any existing integer values to boolean
        DB::statement("UPDATE class_models SET is_active = CASE WHEN CAST(is_active AS CHAR) = '1' OR CAST(is_active AS CHAR) = 't' OR CAST(is_active AS CHAR) = 'true' THEN 1 ELSE 0 END WHERE is_active IS NOT NULL");
        
        // Then alter the column to be boolean with proper default
        Schema::table('class_models', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_models', function (Blueprint $table) {
            $table->integer('is_active')->default(1)->change();
        });
    }
};
