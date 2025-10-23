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
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('student_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->after('class_id')->constrained('users')->onDelete('set null');
            $table->date('date')->nullable()->after('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['class_id', 'teacher_id', 'date']);
        });
    }
};
