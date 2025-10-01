<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'status',
        'scanned_at',
        'student_data',
        'scan_method'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'student_data' => 'array'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}