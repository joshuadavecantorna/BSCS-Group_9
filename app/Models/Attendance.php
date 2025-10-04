<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'status',
        'scanned_at',
        'date',
        'student_data',
        'scan_method'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'student_data' => 'array',
        'date' => 'date'
    ];

    /**
     * Get the student that this attendance record belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the class that this attendance record belongs to
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the teacher who recorded this attendance
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the excuse for this attendance record
     */
    public function excuse(): HasOne
    {
        return $this->hasOne(Excuse::class);
    }

    /**
     * Check if this attendance record has an excuse
     */
    public function hasExcuse(): bool
    {
        return $this->excuse()->exists();
    }

    /**
     * Check if this attendance record has a pending excuse
     */
    public function hasPendingExcuse(): bool
    {
        return $this->excuse()->where('status', 'pending')->exists();
    }

    /**
     * Check if this attendance record has an approved excuse
     */
    public function hasApprovedExcuse(): bool
    {
        return $this->excuse()->where('status', 'approved')->exists();
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by class
     */
    public function scopeByClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope to filter by teacher
     */
    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}