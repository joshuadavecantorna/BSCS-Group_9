<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcuseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'attendance_session_id',
        'reason',
        'attachment_path',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime'
    ];

    /**
     * Get the student that made this excuse request
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the attendance session this request is for
     */
    public function attendanceSession()
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    /**
     * Get the teacher who reviewed this request
     */
    public function reviewer()
    {
        return $this->belongsTo(Teacher::class, 'reviewed_by');
    }

    /**
     * Get the class through the attendance session
     */
    public function class()
    {
        return $this->hasOneThrough(
            ClassModel::class,
            AttendanceSession::class,
            'id', // Foreign key on attendance_sessions table
            'id', // Foreign key on class_models table
            'attendance_session_id', // Local key on excuse_requests table
            'class_id' // Local key on attendance_sessions table
        );
    }
}