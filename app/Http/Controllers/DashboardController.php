<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display role-specific dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        switch ($user->role ?? 'teacher') {
            case 'admin':
                return $this->adminDashboard($request);
            case 'teacher':
                return $this->teacherDashboard($request);
            case 'student':
                return $this->studentDashboard($request);
            default:
                return $this->teacherDashboard($request); // Default fallback
        }
    }

    /**
     * Admin Dashboard - System-wide overview
     */
    private function adminDashboard(Request $request)
    {
        // Get all classes with teacher information
        $classes = ClassModel::with(['teacher', 'teacherRecord'])->get();
        
        // System-wide statistics
        $today = Carbon::today();
        $totalStudents = Student::count();
        $totalClasses = ClassModel::count();
        $totalTeachers = User::where('role', 'teacher')->count();
        
        // Today's attendance statistics
        $todayAttendance = Attendance::whereDate('created_at', $today)->get();
        $attendanceStats = [
            'total' => $totalStudents,
            'present' => $todayAttendance->where('status', 'present')->count(),
            'absent' => $todayAttendance->where('status', 'absent')->count(),
            'excused' => $todayAttendance->where('status', 'excused')->count(),
            'rate' => $totalStudents > 0 ? round(($todayAttendance->where('status', 'present')->count() / $totalStudents) * 100, 1) : 0
        ];

        // Recent activities across system
        $recentActivities = Attendance::with(['student', 'class'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'time' => $attendance->created_at->format('H:i'),
                    'text' => $attendance->student->name . ' marked ' . $attendance->status . ' in ' . $attendance->class->name,
                    'type' => $attendance->status === 'present' ? 'success' : 'warning'
                ];
            });

        // Pending excuses count
        $pendingExcuses = \App\Models\Excuse::where('status', 'pending')->count();

        return Inertia::render('AdminDashboard', [
            'stats' => $attendanceStats,
            'totalClasses' => $totalClasses,
            'totalTeachers' => $totalTeachers,
            'recentActivities' => $recentActivities,
            'pendingExcuses' => $pendingExcuses,
            'classes' => $classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'teacher' => $class->teacher ? $class->teacher->name : 'No Teacher',
                    'studentCount' => $class->getStudentsCountBySection(),
                ];
            })
        ]);
    }

    /**
     * Teacher Dashboard - Class-specific overview
     */
    private function teacherDashboard(Request $request)
    {
        $teacherId = Auth::id();
        
        // Get teacher's classes
        $classes = ClassModel::where('teacher_id', $teacherId)
            ->with(['teacher', 'teacherRecord'])
            ->get();

        // Teacher's class statistics
        $today = Carbon::today();
        $classIds = $classes->pluck('id');
        
        $todayAttendance = Attendance::whereIn('class_id', $classIds)
            ->whereDate('created_at', $today)
            ->get();

        $totalStudents = Student::whereHas('classes', function($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->count();

        $attendanceStats = [
            'total' => $totalStudents,
            'present' => $todayAttendance->where('status', 'present')->count(),
            'absent' => $todayAttendance->where('status', 'absent')->count(),
            'excused' => $todayAttendance->where('status', 'excused')->count(),
        ];

        // Recent activities for teacher's classes
        $recentActivities = Attendance::with(['student', 'class'])
            ->whereIn('class_id', $classIds)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($attendance) {
                return [
                    'time' => $attendance->created_at->format('H:i'),
                    'text' => 'Class ' . $attendance->class->name . ' – ' . $attendance->student->name . ' marked ' . $attendance->status,
                    'type' => $attendance->status === 'present' ? 'success' : 'warning'
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $attendanceStats,
            'recentActivities' => $recentActivities,
            'classes' => $classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'studentCount' => $class->getStudentsCountBySection(),
                ];
            })
        ]);
    }

    /**
     * Student Dashboard - Personal attendance overview
     */
    private function studentDashboard(Request $request)
    {
        $user = Auth::user();
        $student = $user->student ?: Student::where('email', $user->email)->first();
        
        if (!$student) {
            return redirect()->route('profile.edit')
                ->with('error', 'Student record not found. Please contact administrator.');
        }

        // Student's attendance statistics
        $attendanceHistory = Attendance::where('student_id', $student->id)
            ->with(['class', 'excuse'])
            ->latest()
            ->limit(10)
            ->get();

        $totalAttendance = Attendance::where('student_id', $student->id)->count();
        $presentCount = Attendance::where('student_id', $student->id)->where('status', 'present')->count();

        $stats = [
            'total' => $totalAttendance,
            'present' => $presentCount,
            'absent' => Attendance::where('student_id', $student->id)->where('status', 'absent')->count(),
            'excused' => Attendance::where('student_id', $student->id)->where('status', 'excused')->count(),
            'rate' => $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0
        ];

        // Student's classes
        $studentClasses = $student->classes ?? [];

        return Inertia::render('StudentDashboard', [
            'stats' => $stats,
            'recentAttendance' => $attendanceHistory->map(function ($attendance) {
                return [
                    'date' => $attendance->created_at->format('M d, Y'),
                    'time' => $attendance->created_at->format('H:i'),
                    'class' => $attendance->class->name,
                    'status' => $attendance->status,
                    'hasExcuse' => $attendance->excuse !== null,
                ];
            }),
            'classes' => collect($studentClasses)->map(function ($class) {
                return [
                    'id' => $class->id ?? 'N/A',
                    'name' => $class->name ?? 'Unknown Class',
                ];
            })
        ]);
    }
}