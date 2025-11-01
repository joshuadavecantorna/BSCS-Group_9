<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'name',
        'email',
        'class_id',
        'phone',
        'year',
        'course',
        'section',
        'avatar',
        'qr_data',
        'is_active'
    ];

    protected $casts = [
        'qr_data' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Override the mutator to handle PostgreSQL boolean conversion
    public function setIsActiveAttribute($value)
    {
        Log::info('Student setIsActiveAttribute called', [
            'value' => $value,
            'type' => gettype($value)
        ]);
        
        // Convert to PostgreSQL boolean format
        if (is_bool($value)) {
            $this->attributes['is_active'] = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            $this->attributes['is_active'] = $value == 1 ? 'true' : 'false';
        } elseif (is_string($value)) {
            $this->attributes['is_active'] = in_array(strtolower($value), ['true', '1', 'yes', 'on']) ? 'true' : 'false';
        } else {
            $this->attributes['is_active'] = 'false';
        }
        
        Log::info('Student setIsActiveAttribute result', [
            'stored_value' => $this->attributes['is_active']
        ]);
    }

    // Override the accessor to ensure boolean return
    public function getIsActiveAttribute($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function userByEmail()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    // Direct class relationship (single class via class_id)
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Many-to-many relationship (multiple classes via pivot table)
    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_student')
                    ->withPivot(['is_active', 'enrolled_at', 'dropped_at'])
                    ->withTimestamps();
    }

    // Get active enrolled classes
    public function activeClasses()
    {
        return $this->classes()->wherePivot('is_active', true);
    }

    // Generate QR code data
    public function generateQrData(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'name' => $this->name,
            'year' => $this->year,
            'course' => $this->course,
            'section' => $this->section,
            'avatar' => $this->avatar,
        ];
    }
}