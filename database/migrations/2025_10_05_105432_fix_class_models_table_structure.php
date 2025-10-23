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
        Schema::table('class_models', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('class_models', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('class_models', 'course')) {
                $table->string('course')->after('name');
            }
            if (!Schema::hasColumn('class_models', 'section')) {
                $table->string('section')->after('course');
            }
            if (!Schema::hasColumn('class_models', 'subject')) {
                $table->string('subject')->nullable()->after('section');
            }
            if (!Schema::hasColumn('class_models', 'year')) {
                $table->string('year')->after('subject');
            }
            if (!Schema::hasColumn('class_models', 'description')) {
                $table->text('description')->nullable()->after('year');
            }
            if (!Schema::hasColumn('class_models', 'teacher_id')) {
                $table->foreignId('teacher_id')->after('description')->constrained('users');
            }
            if (!Schema::hasColumn('class_models', 'schedule_time')) {
                $table->time('schedule_time')->nullable()->after('teacher_id');
            }
            if (!Schema::hasColumn('class_models', 'schedule_days')) {
                $table->json('schedule_days')->nullable()->after('schedule_time');
            }
            if (!Schema::hasColumn('class_models', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('schedule_days');
            }
        });
        
        // Fix is_active column type if it exists but wrong type
        DB::statement('ALTER TABLE class_models ALTER COLUMN is_active TYPE boolean USING is_active::boolean');
        DB::statement('ALTER TABLE class_models ALTER COLUMN is_active SET DEFAULT true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_models', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'course', 'section', 'subject', 'year', 'description',
                'teacher_id', 'schedule_time', 'schedule_days', 'is_active'
            ]);
        });
    }
};
