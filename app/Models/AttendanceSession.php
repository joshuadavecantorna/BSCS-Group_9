<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'late_minutes_allowed',
        'present_count',
        'absent_count',
        'excused_count',
        'total_students'
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
        try {
            DB::beginTransaction();

            // Get enrolled student IDs
            $enrolledStudents = DB::table('class_student')
                ->where('class_model_id', $this->class_id)
                ->where('status', 'enrolled')
                ->pluck('student_id');

            // Get total enrolled students
            $totalStudents = $enrolledStudents->count();
            
            // Get fresh records directly from database for enrolled students only
            $records = AttendanceRecord::where('attendance_session_id', $this->id)
                ->whereIn('student_id', $enrolledStudents)
                ->get();
            
            // Calculate counts
            $presentCount = $records->where('status', 'present')->count();
            $absentCount = $records->where('status', 'absent')->count();
            $excusedCount = $records->where('status', 'excused')->count();
            
            Log::info('Calculating attendance session counts', [
                'session_id' => $this->id,
                'records_found' => $records->count(),
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'excused_count' => $excusedCount,
                'total_students' => $totalStudents
            ]);
            
            // Update directly in database
            DB::table('attendance_sessions')
                ->where('id', $this->id)
                ->update([
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'excused_count' => $excusedCount,
                    'total_students' => $totalStudents,
                    'updated_at' => now()
                ]);

            // Refresh the model
            $this->refresh();
            
            DB::commit();
            
            Log::info('Successfully updated attendance counts', [
                'session_id' => $this->id,
                'new_present_count' => $this->present_count
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update attendance counts', [
                'session_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAttendanceRateAttribute(): float
    {
        if ($this->total_students == 0) return 0;
        return ($this->present_count / $this->total_students) * 100;
    }
}