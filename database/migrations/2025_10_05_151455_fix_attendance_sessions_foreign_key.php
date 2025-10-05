<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['class_id']);
            
            // Add new foreign key constraint to reference class_models
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['class_id']);
            
            // Restore the original foreign key constraint to classes table
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }
};
