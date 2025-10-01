<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('course');
            $table->string('section');
            $table->string('year');
            $table->foreignId('teacher_id')->constrained('users');
            $table->time('schedule_time');
            $table->json('schedule_days'); // ['monday', 'wednesday', 'friday']
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('classes');
    }
};