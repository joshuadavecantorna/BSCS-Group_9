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
        'schedule',
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

    public function teacherRecord()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'user_id');
    }

    // Direct relationship (students with class_id pointing to this class)
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Many-to-many relationship (students enrolled via pivot table)
    public function enrolledStudents()
    {
        return $this->belongsToMany(Student::class, 'class_student')
                    ->withPivot(['is_active', 'enrolled_at', 'dropped_at'])
                    ->withTimestamps();
    }

    // Get only active enrolled students
    public function activeStudents()
    {
        return $this->enrolledStudents()->wherePivot('is_active', true);
    }

    /**
     * Get students by matching course and section
     */
    public function getStudentsBySection()
    {
        return Student::where('course', $this->course)
            ->where('section', $this->section)
            ->get();
    }

    /**
     * Get students count by matching course and section
     */
    public function getStudentsCountBySection()
    {
        return Student::where('course', $this->course)
            ->where('section', $this->section)
            ->count();
    }
}