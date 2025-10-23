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
        // Drop the existing class_student table
        Schema::dropIfExists('class_student');
        
        // Recreate with correct column name
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_model_id')->constrained('class_models')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('status', ['pending', 'enrolled', 'rejected'])->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            // Prevent duplicate enrollments
            $table->unique(['class_model_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_student');
        
        // Recreate original table
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('class_models')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('status', ['pending', 'enrolled', 'rejected'])->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            // Prevent duplicate enrollments
            $table->unique(['class_id', 'student_id']);
        });
    }
};