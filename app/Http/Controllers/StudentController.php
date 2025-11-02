<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ExcuseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Get the currently authenticated student
     */
    private function getCurrentStudent()
    {
        $user = Auth::user();
        
        // First try to find by user_id
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            // Check if there's an existing student with this email but no user_id
            $existingStudent = Student::where('email', $user->email)->whereNull('user_id')->first();
            
            if ($existingStudent) {
                // Link the existing student to this user
                $existingStudent->update(['user_id' => $user->id]);
                $student = $existingStudent;
            } else {
                // Create a new student record
                $student = Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => 'TEMP-' . $user->id,
                    'course' => 'Not Set',
                    'year' => '1st Year',
                    'section' => 'A'
                ]);
            }
        }
        
        return $student;
    }

    /**
     * Show the student dashboard
     */
    public function dashboard(): Response
    {
        $student = $this->getCurrentStudent();

        // Get enrolled classes count
        $totalClasses = DB::table('class_student')
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->count();

        // Get total attendance records
        $totalAttendance = AttendanceRecord::where('student_id', $student->id)->count();

        // Get present attendance count
        $presentCount = AttendanceRecord::where('student_id', $student->id)
            ->where('status', 'present')
            ->count();

        // Calculate attendance rate
        $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100) : 0;

        // Get enrolled classes with teacher info
        $classes = DB::table('class_student')
            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
            ->join('teachers', 'class_models.teacher_id', '=', 'teachers.id')
            ->where('class_student.student_id', $student->id)
            ->where('class_student.status', 'enrolled')
            ->select(
                'class_models.id',
                'class_models.name',
                'class_models.course',
                'class_models.section',
                'class_models.year',
                'class_models.schedule_time',
                'class_models.schedule_days',
                'class_models.subject',
                DB::raw("CONCAT(teachers.first_name, ' ', teachers.last_name) as teacher_name")
            )
            ->get();

        // Get upcoming classes based on schedule
        $upcomingClasses = $this->getUpcomingClassesBySchedule($student);

        // Get recent excuse requests
        $recentRequests = ExcuseRequest::where('student_id', $student->id)
            ->with(['attendanceSession' => function($query) {
                $query->select('id', 'start_time', 'end_time', 'class_id')
                      ->with('class:id,name,class_code');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent attendance records with class and session info
        $recentAttendance = DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
            ->where('attendance_records.student_id', $student->id)
            ->select(
                'attendance_records.id',
                'attendance_records.status',
                'attendance_records.marked_at',
                'class_models.name as class_name',
                'class_models.course as class_course',
                'attendance_sessions.start_time',
                'attendance_sessions.end_time'
            )
            ->orderBy('attendance_sessions.start_time', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('student/Dashboard', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id,
                'email' => $student->email,
                'course' => $student->course,
                'year' => $student->year,
                'section' => $student->section,
            ],
            'stats' => [
                'totalClasses' => $totalClasses,
                'attendanceRate' => $attendanceRate,
                'presentCount' => $presentCount,
                'totalAttendance' => $totalAttendance,
            ],
            'classes' => $classes,
            'upcomingClasses' => $upcomingClasses,
            'recentRequests' => $recentRequests,
            'recentAttendance' => $recentAttendance,
        ]);
    }

    /**
     * Show the student classes page
     */
    public function classes(): Response
    {
        $student = $this->getCurrentStudent();

        // Get enrolled classes with attendance statistics
        $classes = DB::table('class_student')
            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
            ->join('teachers', 'class_models.teacher_id', '=', 'teachers.user_id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->where('class_student.student_id', $student->id)
            ->where('class_student.status', 'enrolled')
            ->whereRaw('COALESCE(class_models.is_active, true) = true')
            ->select(
                'class_models.id',
                'class_models.name',
                'class_models.course',
                'class_models.section',
                'class_models.year',
                'class_models.schedule_time',
                'class_models.schedule_days',
                'class_models.subject',
                'class_models.description',
                'class_models.room',
                'class_models.class_code',
                'users.name as teacher_name',
                'class_student.enrolled_at',
                DB::raw("(
                    SELECT COUNT(*)
                    FROM attendance_records ar
                    JOIN attendance_sessions asess ON ar.attendance_session_id = asess.id
                    WHERE ar.student_id = {$student->id}
                    AND asess.class_id = class_models.id
                ) as total_sessions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM attendance_records ar
                    JOIN attendance_sessions asess ON ar.attendance_session_id = asess.id
                    WHERE ar.student_id = {$student->id}
                    AND asess.class_id = class_models.id
                    AND ar.status IN ('present', 'late')
                ) as present_count")
            )
            ->get()
            ->map(function ($class) {
                $class->attendance_rate = $class->total_sessions > 0
                    ? round(($class->present_count / $class->total_sessions) * 100)
                    : 0;
                
                // Format schedule display
                $scheduleDays = json_decode($class->schedule_days, true);
                $class->schedule_display = $scheduleDays && $class->schedule_time 
                    ? $this->formatScheduleDisplay($class->schedule_time, $scheduleDays)
                    : 'Schedule TBD';
                
                // Format time only
                $class->formatted_time = $this->formatTime($class->schedule_time);
                
                return $class;
            });

        return Inertia::render('student/Classes', [
            'classes' => $classes,
        ]);
    }

    /**
     * Show the student attendance history page
     */
    public function attendanceHistory(Request $request): Response
    {
        $student = $this->getCurrentStudent();
        $classId = $request->query('class_id');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Build the query for attendance records
        $query = DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
            ->join('teachers', 'class_models.teacher_id', '=', 'teachers.user_id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->where('attendance_records.student_id', $student->id)
            ->select(
                'attendance_records.id',
                'attendance_records.status',
                'attendance_records.marked_at',
                'attendance_records.notes',
                'attendance_records.method',
                'class_models.id as class_id',
                'class_models.name as class_name',
                'class_models.course as class_course',
                'class_models.section as class_section',
                'class_models.room',
                'users.name as teacher_name',
                'attendance_sessions.session_name',
                'attendance_sessions.session_date',
                'attendance_sessions.start_time',
                'attendance_sessions.end_time'
            );

        // Apply filters
        if ($classId) {
            $query->where('class_models.id', $classId);
        }

        if ($status) {
            $query->where('attendance_records.status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('attendance_sessions.session_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('attendance_sessions.session_date', '<=', $dateTo);
        }

        $records = $query->orderBy('attendance_sessions.session_date', 'desc')
            ->orderBy('attendance_sessions.start_time', 'desc')
            ->paginate(20)
            ->through(function ($record) {
                // Format times and add additional display data
                $record->formatted_time = $this->formatTime($record->start_time);
                $record->formatted_date = \Carbon\Carbon::parse($record->session_date)->format('M d, Y');
                $record->status_class = $this->getStatusClass($record->status);
                $record->status_display = ucfirst($record->status);
                
                return $record;
            });

        // Get all enrolled classes for the filter dropdown
        $classes = DB::table('class_student')
            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
            ->where('class_student.student_id', $student->id)
            ->where('class_student.status', 'enrolled')
            ->whereRaw('COALESCE(class_models.is_active, true) = true')
            ->select('class_models.id', 'class_models.name', 'class_models.course', 'class_models.section')
            ->orderBy('class_models.name')
            ->get();

        // Get attendance statistics
        $stats = $this->getAttendanceStats($student, $classId, $dateFrom, $dateTo);

        return Inertia::render('student/AttendanceHistory', [
            'records' => $records,
            'classes' => $classes,
            'stats' => $stats,
            'filters' => [
                'class_id' => $classId,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statusOptions' => [
                ['value' => 'present', 'label' => 'Present'],
                ['value' => 'absent', 'label' => 'Absent'],
                ['value' => 'late', 'label' => 'Late'],
                ['value' => 'excused', 'label' => 'Excused'],
            ]
        ]);
    }

    /**
     * Get attendance statistics for filtering
     */
    private function getAttendanceStats($student, $classId = null, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
            ->where('attendance_records.student_id', $student->id);

        // Apply same filters
        if ($classId) {
            $query->where('class_models.id', $classId);
        }

        if ($dateFrom) {
            $query->whereDate('attendance_sessions.session_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('attendance_sessions.session_date', '<=', $dateTo);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN attendance_records.status = \'present\' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN attendance_records.status = \'absent\' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN attendance_records.status = \'late\' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN attendance_records.status = \'excused\' THEN 1 ELSE 0 END) as excused
        ')->first();

        $total = $stats->total ?? 0;
        
        return [
            'total' => $total,
            'present' => $stats->present ?? 0,
            'absent' => $stats->absent ?? 0,
            'late' => $stats->late ?? 0,
            'excused' => $stats->excused ?? 0,
            'attendance_rate' => $total > 0 ? round((($stats->present + $stats->late) / $total) * 100, 1) : 0
        ];
    }

    /**
     * Get CSS class for status display
     */
    private function getStatusClass($status)
    {
        switch ($status) {
            case 'present':
                return 'success';
            case 'late':
                return 'warning';
            case 'absent':
                return 'danger';
            case 'excused':
                return 'info';
            default:
                return 'secondary';
        }
    }

    /**
     * Show the student excuse requests page
     */
    public function excuseRequests(): Response
    {
        $student = $this->getCurrentStudent();

        $requests = ExcuseRequest::where('student_id', $student->id)
            ->with(['attendanceSession:id,start_time,end_time,class_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get enrolled classes for the form
        $classes = DB::table('class_student')
            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
            ->where('class_student.student_id', $student->id)
            ->where('class_student.status', 'enrolled')
            ->select('class_models.id', 'class_models.name', 'class_models.course')
            ->get();

        return Inertia::render('student/ExcuseRequests', [
            'requests' => $requests,
            'classes' => $classes,
        ]);
    }

    /**
     * Submit a new excuse request
     */
    public function submitExcuseRequest(Request $request)
    {
        $student = $this->getCurrentStudent();

        $validated = $request->validate([
            'attendance_session_id' => 'required|exists:attendance_sessions,id',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        // Verify student is enrolled in the session's class
        $session = DB::table('attendance_sessions')
            ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
            ->join('class_student', 'class_models.id', '=', 'class_student.class_model_id')
            ->where('attendance_sessions.id', $validated['attendance_session_id'])
            ->where('class_student.student_id', $student->id)
            ->where('class_student.status', 'enrolled')
            ->exists();

        if (!$session) {
            return back()->withErrors(['attendance_session_id' => 'You are not enrolled in this session\'s class.']);
        }

        // Handle file upload if present
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('excuse-requests', 'public');
        }

        ExcuseRequest::create([
            'student_id' => $student->id,
            'attendance_session_id' => $validated['attendance_session_id'],
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('student.excuse-requests')->with('success', 'Excuse request submitted successfully.');
    }

    /**
     * Quick check-in for attendance
     */
    public function quickCheckIn(Request $request)
    {
        $student = $this->getCurrentStudent();

        $validated = $request->validate([
            'class_id' => 'required|exists:class_models,id',
            'method' => 'required|in:qr,manual',
            'qr_code' => 'required_if:method,qr|string',
        ]);

        // Verify student is enrolled in the class
        $isEnrolled = DB::table('class_student')
            ->where('student_id', $student->id)
            ->where('class_model_id', $validated['class_id'])
            ->where('status', 'enrolled')
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this class.',
            ], 403);
        }

        // Find an active session for this class
        $session = AttendanceSession::where('class_id', $validated['class_id'])
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active attendance session found for this class.',
            ], 404);
        }

        // If QR method, verify the QR code matches
        if ($validated['method'] === 'qr' && $session->qr_code !== $validated['qr_code']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code.',
            ], 400);
        }

        // Check if already checked in
        $existingRecord = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingRecord) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in for this session.',
            ], 400);
        }

        // Create attendance record
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
            'marked_at' => now(),
            'marked_by' => 'student',
            'notes' => $validated['method'] === 'qr' ? 'QR Code Check-in' : 'Manual Check-in',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully checked in!',
        ]);
    }

    /**
     * Self check-in using student's name and course QR code
     */
    public function selfCheckIn(Request $request)
    {
        Log::info('Student self-checkin request received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        $student = $this->getCurrentStudent();
        Log::info('Current student for self-checkin', ['student_id' => $student->id, 'student_name' => $student->name]);

        $validated = $request->validate([
            'class_id' => 'required|exists:class_models,id',
            'name' => 'required|string',
            'course' => 'required|string',
        ]);

        // Verify student is enrolled in the class
        $isEnrolled = DB::table('class_student')
            ->where('student_id', $student->id)
            ->where('class_model_id', $validated['class_id'])
            ->where('status', 'enrolled')
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this class.',
            ], 403);
        }

        // Find an active session for this class
        $session = AttendanceSession::where('class_id', $validated['class_id'])
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active attendance session found for this class.',
            ], 404);
        }

        // Verify the name and course match the current student
        $normalizedStudentName = strtolower(trim(preg_replace('/\s+/', ' ', $student->name)));
        $normalizedScannedName = strtolower(trim(preg_replace('/\s+/', ' ', $validated['name'])));
        
        // Allow partial name matching
        $nameMatches = strpos($normalizedStudentName, $normalizedScannedName) !== false || 
                      strpos($normalizedScannedName, $normalizedStudentName) !== false;

        if (!$nameMatches) {
            return response()->json([
                'success' => false,
                'message' => 'The scanned name does not match your profile.',
            ], 400);
        }

        // Basic course verification (if student has course info)
        if ($student->course) {
            $normalizedStudentCourse = strtolower(str_replace(' ', '', $student->course));
            $normalizedScannedCourse = strtolower(str_replace(' ', '', $validated['course']));
            
            $courseMatches = strpos($normalizedStudentCourse, str_replace('bs', '', $normalizedScannedCourse)) !== false ||
                           strpos($normalizedScannedCourse, str_replace(['bachelor', 'science'], '', $normalizedStudentCourse)) !== false;
            
            if (!$courseMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'The scanned course does not match your profile.',
                ], 400);
            }
        }

        // Check if already checked in
        $existingRecord = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingRecord) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in for this session.',
            ], 400);
        }

        // Create attendance record
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
            'marked_at' => now(),
            'marked_by' => 'student',
            'notes' => 'Self Check-in via Name+Course QR',
        ]);

        // Update session counts
        $session->updateCounts();

        return response()->json([
            'success' => true,
            'message' => 'Successfully marked as present!',
        ]);
    }

    /**
     * Get upcoming classes based on schedule for today and tomorrow
     */
    private function getUpcomingClassesBySchedule($student)
    {
        $today = now();
        $currentDayOfWeek = strtolower($today->format('l')); // e.g., 'monday', 'tuesday'
        $currentTime = $today->format('H:i');

        // Get all classes student is enrolled in
        $classes = DB::table('class_student')
                     ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                     ->join('teachers', 'class_models.teacher_id', '=', 'teachers.user_id')
                     ->join('users', 'teachers.user_id', '=', 'users.id')
                     ->where('class_student.student_id', $student->id)
                     ->where('class_student.status', 'enrolled')
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
                         'class_models.year',
                         'users.name as teacher_name'
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
                        'time' => $this->formatTime($class->schedule_time),
                        'day' => 'Today',
                        'date' => $today->format('M d, Y'),
                        'year' => $class->year,
                        'teacher_name' => $class->teacher_name,
                        'schedule_display' => $this->formatScheduleDisplay($class->schedule_time, $scheduleDays)
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
                    'time' => $this->formatTime($class->schedule_time),
                    'day' => 'Tomorrow',
                    'date' => $tomorrow->format('M d, Y'),
                    'year' => $class->year,
                    'teacher_name' => $class->teacher_name,
                    'schedule_display' => $this->formatScheduleDisplay($class->schedule_time, $scheduleDays)
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
     * Format time from 24-hour to 12-hour format
     */
    private function formatTime($time)
    {
        if (!$time) return '';
        
        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('g:i A');
        } catch (\Exception $e) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A');
            } catch (\Exception $e2) {
                return $time; // Return as-is if parsing fails
            }
        }
    }

    /**
     * Format schedule display string
     */
    private function formatScheduleDisplay($time, $days)
    {
        // Handle the case where $days might be a JSON string
        if (is_string($days)) {
            $days = json_decode($days, true);
        }
        
        // If $days is still not an array or is empty, return a fallback
        if (!is_array($days) || empty($days)) {
            return $this->formatTime($time) ?: 'Schedule TBD';
        }
        
        $formattedTime = $this->formatTime($time);
        $dayAbbrevs = array_map(function($day) {
            return substr(ucfirst($day), 0, 3);
        }, $days);
        
        return implode(', ', $dayAbbrevs) . ' • ' . $formattedTime;
    }
}
