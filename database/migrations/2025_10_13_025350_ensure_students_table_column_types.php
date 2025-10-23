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
    DB::statement("UPDATE students SET is_active = CASE WHEN CAST(is_active AS CHAR) IN ('1', 't', 'true', 'TRUE', 'yes', 'YES') THEN 1 ELSE 0 END WHERE is_active IS NOT NULL");
    Schema::table('students', function (Blueprint $table) {
        $table->boolean('is_active')->default(true)->nullable(false)->change();
        $table->timestamp('created_at')->nullable(false)->useCurrent()->change();
        $table->timestamp('updated_at')->nullable(false)->useCurrent()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as we're just ensuring correct types
    }
};
