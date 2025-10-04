<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('teacher_id')->unique(); // e.g., TEACH-001
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable(); // Professor, Associate Professor, etc.
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['department', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};
