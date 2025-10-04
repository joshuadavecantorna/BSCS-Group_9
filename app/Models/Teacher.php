<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teacher_id',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'department',
        'position',
        'salary',
        'profile_picture',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'salary' => 'decimal:2'
    ];

    /**
     * Get the user that owns the teacher record
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the classes taught by this teacher
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'teacher_id', 'user_id');
    }

    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute()
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        if ($this->middle_name) {
            $name = $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
        }
        return $name;
    }

    /**
     * Get the name for display (falls back to user name if teacher names not set)
     */
    public function getDisplayNameAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return $this->full_name;
        }
        return $this->user ? $this->user->name : 'Unknown Teacher';
    }
}