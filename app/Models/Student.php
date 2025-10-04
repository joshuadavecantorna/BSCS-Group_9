<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'email',
        'class_id',
        'year',
        'course',
        'section',
        'avatar',
        'qr_data',
        'is_active'
    ];

    protected $casts = [
        'qr_data' => 'array',
        'is_active' => 'boolean'
    ];

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function user()
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