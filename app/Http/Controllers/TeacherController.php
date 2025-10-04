<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ClassFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Display the teacher dashboard
     */
    public function dashboard()
    {
        $teacher = $this->getCurrentTeacher();
        $stats = $this->getDashboardStats($teacher);
        $recentActivity = $this->getRecentActivity($teacher);

        return Inertia::render('Teacher/Dashboard', [
            'teacher' => $teacher,
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ]);
    }

    /**
     * Display teacher's classes
     */
    public function classes()
    {
        $teacher = $this->getCurrentTeacher();
        
        // Get classes directly from database with simple query
        $classes = DB::table('classes')
                     ->where('teacher_id', $teacher->id)
                     ->get()
                     ->map(function ($class) {
                         return [
                             'id' => $class->id,
                             'name' => $class->name,
                             'course' => $class->course,
                             'section' => $class->section,
                             'year' => $class->year,
                             'schedule_time' => $class->schedule_time,
                             'schedule_days' => is_string($class->schedule_days) 
                                 ? json_decode($class->schedule_days, true) 
                                 : $class->schedule_days,
                             'student_count' => 25, // Mock data for now
                             'is_active' => $class->is_active
                         ];
                     });

        return Inertia::render('Teacher/Classes', [
            'teacher' => $teacher,
            'classes' => $classes
        ]);
    }

    /**
     * Create a new class
     */
    public function createClass(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_code' => 'required|string|max:20|unique:classes',
            'section' => 'required|string|max:10',
            'subject' => 'required|string|max:255',
            'course' => 'required|string|max:100',
            'year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'room' => 'nullable|string|max:100',
            'schedule_time' => 'nullable|string',
            'schedule_days' => 'nullable|array',
            'academic_year' => 'required|string|max:10',
            'semester' => 'required|string|max:20'
        ]);

        $teacher = $this->getCurrentTeacher();

        $class = ClassModel::create([
            'name' => $request->name,
            'class_code' => $request->class_code,
            'section' => $request->section,
            'subject' => $request->subject,
            'course' => $request->course,
            'year' => $request->year,
            'description' => $request->description,
            'room' => $request->room,
            'schedule_time' => $request->schedule_time,
            'schedule_days' => $request->schedule_days,
            'academic_year' => $request->academic_year,
            'semester' => $request->semester,
            'teacher_id' => $teacher->user_id, // Assuming teacher_id links to user
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'class' => $class
        ]);
    }

    /**
     * Get class details with students and attendance records
     */
    public function getClass($id)
    {
        $teacher = $this->getCurrentTeacher();
        
        $class = ClassModel::with([
            'students' => function ($query) {
                $query->where('class_student.status', 'enrolled');
            },
            'attendanceSessions' => function ($query) {
                $query->latest('session_date');
            },
            'classFiles'
        ])->forTeacher($teacher->id)
          ->findOrFail($id);

        return response()->json([
            'success' => true,
            'class' => $class
        ]);
    }

    /**
     * Update class information
     */
    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_code' => 'required|string|max:20|unique:classes,class_code,' . $id,
            'section' => 'required|string|max:10',
            'subject' => 'required|string|max:255',
            'course' => 'required|string|max:100',
            'year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'room' => 'nullable|string|max:100',
            'schedule_time' => 'nullable|string',
            'schedule_days' => 'nullable|array'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($id);

        $class->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'class' => $class
        ]);
    }

    /**
     * Delete (deactivate) a class
     */
    public function deleteClass($id)
    {
        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($id);

        $class->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Add students to a class (bulk upload)
     */
    public function addStudentsToClass(Request $request, $classId)
    {
        $request->validate([
            'students' => 'required|array',
            'students.*.student_id' => 'required|string|max:50',
            'students.*.first_name' => 'required|string|max:100',
            'students.*.last_name' => 'required|string|max:100',
            'students.*.email' => 'nullable|email',
            'students.*.phone' => 'nullable|string|max:20',
            'students.*.year' => 'required|string|max:20',
            'students.*.course' => 'required|string|max:100',
            'students.*.section' => 'required|string|max:10'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

        $addedStudents = [];

        foreach ($request->students as $studentData) {
            // Create or find student
            $student = Student::firstOrCreate(
                ['student_id' => $studentData['student_id']],
                $studentData
            );

            // Attach to class if not already attached
            $attached = $class->students()->syncWithoutDetaching([
                $student->id => [
                    'status' => 'enrolled',
                    'enrolled_at' => now()
                ]
            ]);

            if (!empty($attached['attached'])) {
                $addedStudents[] = $student;
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($addedStudents) . ' students added successfully',
            'students' => $addedStudents
        ]);
    }

    /**
     * Start attendance session
     */
    public function startAttendanceSession(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_name' => 'required|string|max:200',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'attendance_method' => 'required|in:qr,manual,webcam'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($request->class_id);
        $totalStudents = $class->student_count;

        $session = AttendanceSession::create([
            'class_id' => $request->class_id,
            'teacher_id' => $teacher->id,
            'session_name' => $request->session_name,
            'session_date' => $request->session_date,
            'start_time' => Carbon::parse($request->session_date . ' ' . $request->start_time),
            'attendance_method' => $request->attendance_method,
            'status' => 'active',
            'total_students' => $totalStudents,
            'present_count' => 0,
            'absent_count' => 0,
            'excused_count' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance session started successfully',
            'session' => $session
        ]);
    }

    /**
     * Mark student attendance
     */
    public function markAttendance(Request $request, $sessionId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,excused,late',
            'method' => 'required|in:qr,manual,webcam',
            'notes' => 'nullable|string'
        ]);

        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        $record = AttendanceRecord::updateOrCreate(
            [
                'session_id' => $sessionId,
                'student_id' => $request->student_id
            ],
            [
                'status' => $request->status,
                'method' => $request->get('method'),
                'notes' => $request->notes,
                'check_in_time' => in_array($request->status, ['present', 'late']) ? now() : null
            ]
        );

        // Update session counts
        $session->updateCounts();

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'record' => $record
        ]);
    }

    /**
     * End attendance session
     */
    public function endAttendanceSession($sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        $session->update([
            'status' => 'completed',
            'end_time' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance session ended successfully',
            'session' => $session
        ]);
    }

    /**
     * Upload file to class
     */
    public function uploadFile(Request $request, $classId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('class-files/' . $classId, $fileName, 'public');

        $classFile = ClassFile::create([
            'class_id' => $classId,
            'teacher_id' => $teacher->id,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_url' => Storage::url($filePath),
            'description' => $request->description,
            'is_public' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file' => $classFile
        ]);
    }

    /**
     * Get attendance reports
     */
    public function getAttendanceReports(Request $request)
    {
        $teacher = $this->getCurrentTeacher();
        
        $query = AttendanceSession::with(['class', 'attendanceRecords.student'])
                                 ->where('teacher_id', $teacher->id);

        // Apply filters
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->start_date) {
            $query->where('session_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('session_date', '<=', $request->end_date);
        }

        $sessions = $query->latest('session_date')->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    /**
     * Get current teacher
     */
    private function getCurrentTeacher()
    {
        // Assuming you have authentication setup
        $user = Auth::user();
        return Teacher::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($teacher)
    {
        $today = Carbon::today();
        
        // Get total classes and students using simple queries
        $totalClasses = DB::table('classes')
                           ->where('teacher_id', $teacher->id)
                           ->count();
        
        $totalStudents = DB::table('students')->count();
        
        // Get today's attendance sessions count (if any exist)
        $todaySessions = DB::table('attendance_sessions')
                            ->where('teacher_id', $teacher->id)
                            ->whereDate('session_date', $today)
                            ->count();

        return [
            'totalClasses' => $totalClasses,
            'totalStudents' => $totalStudents,
            'todayPresent' => 0, // Will be calculated once attendance data exists
            'todayAbsent' => 0,
            'todayExcused' => 0,
            'todayDropped' => 0,
            'todaySessions' => $todaySessions,
            'weeklyAttendanceRate' => 0, // Will be calculated once attendance data exists
            'monthlyAttendanceRate' => 0
        ];
    }

    /**
     * Calculate attendance rate for a period
     */
    private function calculateAttendanceRate($teacher, $startDate, $endDate)
    {
        $sessions = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->whereBetween('session_date', [$startDate, $endDate])
                                   ->get();

        $totalStudents = $sessions->sum('total_students');
        $totalPresent = $sessions->sum('present_count');

        return $totalStudents > 0 ? round(($totalPresent / $totalStudents) * 100, 1) : 0;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($teacher)
    {
        // Return mock recent activity data for now
        return [
            [
                'time' => '9:00 AM',
                'text' => 'Started attendance for Introduction to Programming',
                'type' => 'info'
            ],
            [
                'time' => '8:45 AM',
                'text' => 'Updated class schedule for Data Structures',
                'type' => 'success'
            ],
            [
                'time' => '8:30 AM',
                'text' => 'New student enrolled in class',
                'type' => 'success'
            ],
            [
                'time' => '8:15 AM',
                'text' => 'Attendance reminder sent to students',
                'type' => 'info'
            ]
        ];
    }

    /**
     * Display the attendance page
     */
    public function attendance()
    {
        $teacher = $this->getCurrentTeacher();
        
        // Get teacher's classes
        $classes = DB::table('classes')
                     ->where('teacher_id', $teacher->id)
                     ->get()
                     ->map(function ($class) {
                         $studentCount = DB::table('class_students')
                                          ->where('class_id', $class->id)
                                          ->count();
                         
                         return [
                             'id' => $class->id,
                             'class_name' => $class->name,
                             'course_code' => $class->course ?? 'N/A',
                             'student_count' => $studentCount,
                         ];
                     });

        // Get recent attendance sessions
        $recentSessions = DB::table('attendance_sessions')
                           ->join('classes', 'attendance_sessions.class_id', '=', 'classes.id')
                           ->where('classes.teacher_id', $teacher->id)
                           ->orderBy('attendance_sessions.created_at', 'desc')
                           ->take(5)
                           ->get([
                               'attendance_sessions.id',
                               'classes.name as class_name',
                               'classes.course',
                               'attendance_sessions.created_at',
                               'attendance_sessions.status'
                           ])
                           ->map(function ($session) {
                               $attendanceStats = DB::table('attendance_records')
                                                   ->where('attendance_session_id', $session->id)
                                                   ->selectRaw('
                                                       COUNT(*) as total_count,
                                                       SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count
                                                   ')
                                                   ->first();

                               $date = Carbon::parse($session->created_at);
                               
                               return [
                                   'id' => $session->id,
                                   'class_name' => $session->class_name,
                                   'course_code' => $session->course ?? 'N/A',
                                   'date' => $date->format('M d, Y'),
                                   'time' => $date->format('g:i A'),
                                   'present_count' => $attendanceStats->present_count ?? 0,
                                   'total_count' => $attendanceStats->total_count ?? 0,
                                   'status' => $session->status ?? 'active',
                               ];
                           });

        return Inertia::render('Teacher/Attendance', [
            'teacher' => $teacher,
            'classes' => $classes,
            'recent_sessions' => $recentSessions,
        ]);
    }

    /**
     * Display the files page
     */
    public function files()
    {
        $teacher = $this->getCurrentTeacher();

        return Inertia::render('Teacher/Files', [
            'teacher' => $teacher
        ]);
    }

    /**
     * Display the reports page
     */
    public function reports()
    {
        $teacher = $this->getCurrentTeacher();

        return Inertia::render('Teacher/Reports', [
            'teacher' => $teacher
        ]);
    }

    /**
     * Display the settings page
     */
    public function settings()
    {
        $teacher = $this->getCurrentTeacher();

        return Inertia::render('Teacher/Settings', [
            'teacher' => $teacher
        ]);
    }

    /**
     * Update attendance records for a session
     */
    public function updateAttendanceRecords(Request $request, $sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        
        // Verify the session belongs to the teacher
        $session = DB::table('attendance_sessions')
                    ->join('classes', 'attendance_sessions.class_id', '=', 'classes.id')
                    ->where('attendance_sessions.id', $sessionId)
                    ->where('classes.teacher_id', $teacher->id)
                    ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found or access denied'], 404);
        }

        $validated = $request->validate([
            'attendance_records' => 'required|array',
            'attendance_records.*.student_id' => 'required|integer',
            'attendance_records.*.status' => 'required|in:present,absent,late,excused'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['attendance_records'] as $record) {
                DB::table('attendance_records')
                  ->updateOrInsert(
                      [
                          'attendance_session_id' => $sessionId,
                          'student_id' => $record['student_id']
                      ],
                      [
                          'status' => $record['status'],
                          'updated_at' => now()
                      ]
                  );
            }

            DB::commit();
            return redirect()->route('teacher.attendance')->with('success', 'Attendance records updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update attendance records: ' . $e->getMessage()]);
        }
    }
}