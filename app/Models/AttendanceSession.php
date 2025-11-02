<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'teacher_id',
        'session_name',
        'session_date',
        'start_time',
        'end_time',
        'duration',
        'status',
        'notes',
        'qr_code',
        'allow_late_attendance',
        'late_minutes_allowed'
    ];

    protected $casts = [
        'session_date' => 'date',
        'allow_late_attendance' => 'boolean',
        'late_minutes_allowed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'attendance_session_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('session_date', today());
    }

    // Methods
    public function updateCounts()
    {
        $records = $this->attendanceRecords;
        
        $this->update([
            'present_count' => $records->where('status', 'present')->count(),
            'absent_count' => $records->where('status', 'absent')->count(),
            'excused_count' => $records->where('status', 'excused')->count()
        ]);
    }

    public function getAttendanceRateAttribute(): float
    {
        if ($this->total_students == 0) return 0;
        return ($this->present_count / $this->total_students) * 100;
    }
}