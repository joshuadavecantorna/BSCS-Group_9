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
        if (!Schema::hasColumn('class_models', 'class_code')) {
            Schema::table('class_models', function (Blueprint $table) {
                $table->string('class_code')->nullable()->after('course');
            });
        }
        
        // Generate class codes for existing records
        DB::statement("UPDATE class_models SET class_code = CONCAT(course, '-', section, '-', year, '-', UNIX_TIMESTAMP(created_at)) WHERE class_code IS NULL");
        
        // Make class_code required and unique after populating existing records
        Schema::table('class_models', function (Blueprint $table) {
            $table->string('class_code')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('class_models', 'class_code')) {
            Schema::table('class_models', function (Blueprint $table) {
                $table->dropColumn('class_code');
            });
        }
    }
};
