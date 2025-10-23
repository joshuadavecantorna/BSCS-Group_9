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
        DB::statement("UPDATE class_models SET is_active = CASE WHEN is_active::text = '1' OR is_active::text = 't' OR is_active::text = 'true' THEN true ELSE false END WHERE is_active IS NOT NULL");
        
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
