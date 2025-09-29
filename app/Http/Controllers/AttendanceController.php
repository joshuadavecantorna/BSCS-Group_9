<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'name' => 'required|string',
            'year' => 'required|string',
            'course' => 'required|string',
            'section' => 'required|string',
        ]);

        // Check if student exists
        $student = Student::where('student_id', $validated['id'])->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in database'
            ], 404);
        }

        // Check for duplicate attendance today
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded today',
                'duplicate' => true
            ], 409);
        }

        // Create attendance record
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'teacher_id' => auth()->id(),
            'status' => 'present',
            'scanned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => $attendance
        ]);
    }
}