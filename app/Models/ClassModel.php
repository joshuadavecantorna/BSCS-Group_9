<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'class_models';

    protected $fillable = [
        'name',
        'course',
        'class_code',
        'section',
        'year',
        'subject',
        'description',
        'teacher_id',
        'schedule',
        'schedule_time',
        'schedule_days',
        'is_active'
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'is_active' => 'boolean',
        'schedule_time' => 'datetime:H:i',
    ];

    // Ensure boolean values are properly handled
    public function setIsActiveAttribute($value)
    {
        $this->attributes['is_active'] = (bool) $value;
    }

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

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }

    public function classFiles()
    {
        return $this->hasMany(ClassFile::class, 'class_id');
    }

    // Scopes
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 'true');
    }

    // Accessors
    public function getStudentCountAttribute()
    {
        return $this->students()->count();
    }

    public function getLastSessionAttribute()
    {
        return $this->attendanceSessions()
                    ->latest('session_date')
                    ->first();
    }
}