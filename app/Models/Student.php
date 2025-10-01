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

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_student');
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