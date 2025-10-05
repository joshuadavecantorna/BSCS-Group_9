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

    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student');
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