

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('status'); // present, absent, excused
            $table->timestamp('scanned_at');
            $table->json('student_data')->nullable(); // Store scanned student data
            $table->string('scan_method')->default('qr'); // qr, manual
            $table->timestamps();
            
            // Index for better performance
            $table->index(['student_id', 'scanned_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};