<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;

class StudentDashboardController extends Controller
{
    /**
     * Show the student dashboard.
     */
    public function index(): Response
    {
        $student = $this->getCurrentStudent();
        $stats = $this->getDashboardStats($student);
        $upcomingClasses = $this->getUpcomingClasses($student);
        $recentActivity = $this->getRecentActivity($student);

        return Inertia::render('student/Dashboard', [
            'student' => $student,
            'stats' => $stats,
            'upcomingClasses' => $upcomingClasses,
            'recentActivity' => $recentActivity
        ]);
    }

    /**
     * Show the student classes page.
     */
    public function classes(): Response
    {
        $student = $this->getCurrentStudent();
        
        // Get all enrolled classes with detailed information
        $classes = DB::table('class_student')
                     ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                     ->join('teachers', 'class_models.teacher_id', '=', 'teachers.user_id')
                     ->join('users', 'teachers.user_id', '=', 'users.id')
                     ->where('class_student.student_id', $student->id)
                     ->whereRaw('COALESCE(class_models.is_active, true) = true')
                     ->select(
                         'class_models.id',
                         'class_models.name',
                         'class_models.course',
                         'class_models.section', 
                         'class_models.subject',
                         'class_models.year',
                         'class_models.room',
                         'class_models.schedule_time',
                         'class_models.schedule_days',
                         'class_models.class_code',
                         'users.name as teacher_name',
                         'class_student.enrolled_at'
                     )
                     ->orderBy('class_models.name')
                     ->get()
                     ->map(function ($class) {
                         return [
                             'id' => $class->id,
                             'name' => $class->name,
                             'course' => $class->course,
                             'section' => $class->section,
                             'subject' => $class->subject,
                             'year' => $class->year,
                             'room' => $class->room ?? 'TBD',
                             'schedule_time' => $class->schedule_time,
                             'schedule_days' => json_decode($class->schedule_days, true) ?? [],
                             'class_code' => $class->class_code,
                             'teacher_name' => $class->teacher_name,
                             'enrolled_at' => $class->enrolled_at
                         ];
                     });

        return Inertia::render('student/Classes', [
            'student' => $student,
            'classes' => $classes
        ]);
    }

    /**
     * Show the student attendance history page.
     */
    public function attendanceHistory(): Response
    {
        $student = $this->getCurrentStudent();
        
        // Get attendance history with session and class details
        $attendanceHistory = DB::table('attendance_records')
                               ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                               ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                               ->where('attendance_records.student_id', $student->id)
                               ->orderBy('attendance_sessions.session_date', 'desc')
                               ->orderBy('attendance_sessions.start_time', 'desc')
                               ->select(
                                   'attendance_records.*',
                                   'attendance_sessions.session_name',
                                   'attendance_sessions.session_date',
                                   'attendance_sessions.start_time',
                                   'class_models.name as class_name',
                                   'class_models.course',
                                   'class_models.section'
                               )
                               ->paginate(20);

        return Inertia::render('student/AttendanceHistory', [
            'student' => $student,
            'attendanceHistory' => $attendanceHistory
        ]);
    }

    /**
     * Show the student excuse requests page.
     */
    public function excuseRequests(): Response
    {
        return Inertia::render('student/ExcuseRequests');
    }

    /**
     * Get current authenticated student
     */
    private function getCurrentStudent()
    {
        $user = Auth::user();
        return Student::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * Get dashboard statistics for student
     */
    private function getDashboardStats($student)
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        // Get total enrolled classes
        $totalClasses = DB::table('class_student')
                          ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                          ->where('class_student.student_id', $student->id)
                          ->whereRaw('COALESCE(class_models.is_active, true) = true')
                          ->count();

        // Get attendance statistics
        $attendanceStats = DB::table('attendance_records')
                             ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                             ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                             ->join('class_student', function($join) use ($student) {
                                 $join->on('class_models.id', '=', 'class_student.class_model_id')
                                      ->where('class_student.student_id', $student->id);
                             })
                             ->where('attendance_records.student_id', $student->id)
                             ->selectRaw('
                                 COUNT(*) as total_sessions,
                                 SUM(CASE WHEN attendance_records.status IN (\'present\', \'late\') THEN 1 ELSE 0 END) as present_count,
                                 SUM(CASE WHEN attendance_records.status = \'absent\' THEN 1 ELSE 0 END) as absent_count,
                                 SUM(CASE WHEN attendance_records.status = \'excused\' THEN 1 ELSE 0 END) as excused_count
                             ')
                             ->first();

        $attendanceRate = $attendanceStats && $attendanceStats->total_sessions > 0 
                         ? round(($attendanceStats->present_count / $attendanceStats->total_sessions) * 100, 1) 
                         : 0;

        // Get today's sessions
        $todaySessions = DB::table('attendance_sessions')
                           ->join('class_student', 'attendance_sessions.class_id', '=', 'class_student.class_model_id')
                           ->where('class_student.student_id', $student->id)
                           ->whereDate('attendance_sessions.session_date', $today)
                           ->count();

        return [
            'totalClasses' => $totalClasses,
            'attendanceRate' => $attendanceRate,
            'totalSessions' => $attendanceStats ? $attendanceStats->total_sessions : 0,
            'presentCount' => $attendanceStats ? $attendanceStats->present_count : 0,
            'absentCount' => $attendanceStats ? $attendanceStats->absent_count : 0,
            'excusedCount' => $attendanceStats ? $attendanceStats->excused_count : 0,
            'todaySessions' => $todaySessions,
            'lastUpdated' => now()->toISOString()
        ];
    }

    /**
     * Get upcoming classes based on schedule
     */
    private function getUpcomingClasses($student)
    {
        $today = Carbon::now();
        $currentDayOfWeek = strtolower($today->format('l')); // e.g., 'monday', 'tuesday'
        $currentTime = $today->format('H:i');

        // Get all classes student is enrolled in
        $classes = DB::table('class_student')
                     ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                     ->where('class_student.student_id', $student->id)
                     ->whereRaw('COALESCE(class_models.is_active, true) = true')
                     ->select(
                         'class_models.id',
                         'class_models.name',
                         'class_models.course',
                         'class_models.section',
                         'class_models.subject',
                         'class_models.room',
                         'class_models.schedule_time',
                         'class_models.schedule_days',
                         'class_models.year'
                     )
                     ->get();

        $upcomingClasses = [];

        foreach ($classes as $class) {
            if (!$class->schedule_days || !$class->schedule_time) {
                continue; // Skip classes without schedule information
            }

            $scheduleDays = json_decode($class->schedule_days, true);
            if (!is_array($scheduleDays)) {
                continue;
            }

            // Check if class is scheduled for today or tomorrow
            $classDays = array_map('strtolower', $scheduleDays);
            
            // For today
            if (in_array($currentDayOfWeek, $classDays)) {
                // Check if class time hasn't passed yet today
                if ($class->schedule_time > $currentTime) {
                    $upcomingClasses[] = [
                        'id' => $class->id,
                        'name' => $class->name,
                        'course' => $class->course,
                        'section' => $class->section,
                        'subject' => $class->subject,
                        'room' => $class->room ?? 'TBD',
                        'time' => $class->schedule_time,
                        'day' => 'Today',
                        'date' => $today->format('M d, Y'),
                        'year' => $class->year
                    ];
                }
            }

            // For tomorrow
            $tomorrow = $today->copy()->addDay();
            $tomorrowDayOfWeek = strtolower($tomorrow->format('l'));
            
            if (in_array($tomorrowDayOfWeek, $classDays)) {
                $upcomingClasses[] = [
                    'id' => $class->id,
                    'name' => $class->name,
                    'course' => $class->course,
                    'section' => $class->section,
                    'subject' => $class->subject,
                    'room' => $class->room ?? 'TBD',
                    'time' => $class->schedule_time,
                    'day' => 'Tomorrow',
                    'date' => $tomorrow->format('M d, Y'),
                    'year' => $class->year
                ];
            }
        }

        // Sort by date and time
        usort($upcomingClasses, function($a, $b) {
            if ($a['day'] === $b['day']) {
                return strcmp($a['time'], $b['time']);
            }
            return $a['day'] === 'Today' ? -1 : 1;
        });

        return array_slice($upcomingClasses, 0, 5); // Return next 5 classes
    }

    /**
     * Get recent activity for student
     */
    private function getRecentActivity($student)
    {
        $activities = [];

        // Get recent attendance records
        $recentAttendance = DB::table('attendance_records')
                             ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                             ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                             ->where('attendance_records.student_id', $student->id)
                             ->orderBy('attendance_records.created_at', 'desc')
                             ->limit(5)
                             ->select(
                                 'attendance_records.*',
                                 'attendance_sessions.session_name',
                                 'class_models.name as class_name',
                                 'class_models.course'
                             )
                             ->get();

        foreach ($recentAttendance as $record) {
            $time = Carbon::parse($record->created_at)->format('g:i A');
            $date = Carbon::parse($record->created_at)->format('M d');
            
            $activities[] = [
                'type' => 'attendance',
                'title' => 'Attendance Marked',
                'description' => "Marked as {$record->status} for {$record->class_name}",
                'time' => $time,
                'date' => $date,
                'status' => $record->status,
                'icon' => $record->status === 'present' ? 'check' : ($record->status === 'absent' ? 'x' : 'clock')
            ];
        }

        // Sort by creation time
        usort($activities, function($a, $b) {
            return strcmp($b['date'] . ' ' . $b['time'], $a['date'] . ' ' . $a['time']);
        });

        return array_slice($activities, 0, 6);
    }
}
