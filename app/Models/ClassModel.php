<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'course',
        'section',
        'year',
        'teacher_id',
        'schedule_time',
        'schedule_days',
        'is_active'
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'is_active' => 'boolean'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student');
    }
}