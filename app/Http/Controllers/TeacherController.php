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
use Illuminate\Support\Facades\Log;
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
        $classes = DB::table('class_models')
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
                             'schedule_days' => $class->schedule_days ?? [],
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
            'course' => 'required|string|max:100',
            'section' => 'required|string|max:10',
            'subject' => 'nullable|string|max:255',
            'year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'schedule_time' => 'nullable|date_format:H:i',
            'schedule_days' => 'nullable|array',
        ]);

        $teacher = $this->getCurrentTeacher();

        // Generate unique class code
        $classCode = $this->generateUniqueClassCode($request->course, $request->section, $request->year);

        $class = ClassModel::create([
            'name' => $request->name,
            'course' => $request->course,
            'class_code' => $classCode,
            'section' => $request->section,
            'subject' => $request->subject,
            'year' => $request->year,
            'description' => $request->description,
            'schedule_time' => $request->schedule_time,
            'schedule_days' => $request->schedule_days,
            'teacher_id' => $teacher->id,
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
        
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($id);
        
        // Get students for this class
        $students = DB::table('class_student')
                     ->join('students', 'class_student.student_id', '=', 'students.id')
                     ->where('class_student.class_model_id', $id)
                     ->select(
                         'students.id',
                         'students.student_id',
                         'students.name',
                         'students.email',
                         'students.year',
                         'students.course',
                         'students.section',
                         'class_student.status',
                         'class_student.enrolled_at'
                     )
                     ->get();

        // Get recent attendance sessions
        $recentSessions = DB::table('attendance_sessions')
                           ->where('class_id', $id)
                           ->orderBy('session_date', 'desc')
                           ->take(5)
                           ->get();

        return Inertia::render('Teacher/ClassDetails', [
            'teacher' => $teacher,
            'classData' => [
                'id' => $class->id,
                'name' => $class->name,
                'course' => $class->course,
                'section' => $class->section,
                'year' => $class->year,
                'schedule_time' => $class->schedule_time,
                'schedule_days' => $class->schedule_days ?? []
            ],
            'students' => $students,
            'recentSessions' => $recentSessions
        ]);
    }

    /**
     * Update class information
     */
    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'course' => 'required|string|max:100|unique:class_models,course,' . $id,
            'section' => 'required|string|max:10',
            'subject' => 'nullable|string|max:255',
            'year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'schedule_time' => 'nullable|date_format:H:i',
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
     * Get students in a specific class
     */
    public function getClassStudents($classId)
    {
        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

                    $classStudents = DB::table('class_student')
                              ->join('students', 'class_student.student_id', '=', 'students.id')
                              ->where('class_student.class_model_id', $class->id)
                              ->select(
                                  'students.*',
                                  'class_student.status',
                                  'class_student.enrolled_at'
                              )
                              ->get();

        return response()->json([
            'success' => true,
            'students' => $classStudents,
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'course' => $class->course,
                'section' => $class->section
            ]
        ]);
    }

    /**
     * Add students to a class (single or bulk upload)
     */
    public function addStudentsToClass(Request $request, $classId)
    {
        // Check if this is a single student addition or bulk upload
        if ($request->has('student_id') && !$request->has('students')) {
            // Single student addition
            $request->validate([
                'student_id' => 'required|string|max:50',
                'name' => 'required|string|max:200',
                'email' => 'nullable|email',
                'year' => 'required|string|max:20',
                'course' => 'required|string|max:100',
                'section' => 'required|string|max:10'
            ]);

            $students = [$request->all()];
        } else {
            // Bulk upload - support both formats
            $request->validate([
                'students' => 'required|array',
            ]);

            $students = [];
            foreach ($request->students as $studentData) {
                // All student data should have a name field
                if (!isset($studentData['name'])) {
                    // If first_name and last_name are provided, combine them
                    if (isset($studentData['first_name']) && isset($studentData['last_name'])) {
                        $studentData['name'] = trim($studentData['first_name'] . ' ' . $studentData['last_name']);
                    } else {
                        throw new \Exception('Student name is required');
                    }
                }
                
                $students[] = [
                    'student_id' => $studentData['student_id'],
                    'name' => $studentData['name'],
                    'email' => $studentData['email'] ?? null,
                    'year' => $studentData['year'],
                    'course' => $studentData['course'],
                    'section' => $studentData['section'],
                    'is_active' => true
                ];
            }
        }

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

        $addedStudents = [];

        foreach ($students as $studentData) {
            // Create or find student
            $student = Student::firstOrCreate(
                ['student_id' => $studentData['student_id']],
                $studentData
            );

            // Attach to class using direct DB insertion since the relationship might not be properly set up
            $exists = DB::table('class_student')
                       ->where('class_model_id', $classId)
                       ->where('student_id', $student->id)
                       ->exists();

            if (!$exists) {
                DB::table('class_student')->insert([
                    'class_model_id' => $classId,
                    'student_id' => $student->id,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

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
     * Remove student from class
     */
    public function removeStudentFromClass($classId, $studentId)
    {
        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

                $removed = DB::table('class_student')
                    ->where('class_model_id', $classId)
                    ->where('student_id', $studentId)
                    ->delete();

        if ($removed) {
            return response()->json([
                'success' => true,
                'message' => 'Student removed from class successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Student not found in class'
        ], 404);
    }

    /**
     * Start attendance session
     */
    public function startAttendanceSession(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_models,id',
            'session_name' => 'required|string|max:200',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'attendance_method' => 'required|in:qr,manual,webcam'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($request->class_id);
        $totalStudents = DB::table('class_student')->where('class_model_id', $request->class_id)->count();

        // Generate unique QR code for this session
        $qrCode = 'session_' . uniqid() . '_' . time();

        $session = AttendanceSession::create([
            'class_id' => $request->class_id,
            'teacher_id' => $teacher->id,
            'session_name' => $request->session_name,
            'session_date' => $request->session_date,
            'start_time' => $request->start_time,
            'status' => 'active',
            'qr_code' => $qrCode
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance session started successfully',
            'session' => $session
        ]);
    }

    /**
     * Quick start attendance session for a class
     */
    public function quickStartSession(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_models,id'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($request->class_id);
        $totalStudents = DB::table('class_student')->where('class_model_id', $request->class_id)->count();

        // Generate unique QR code for this session
        $qrCode = 'session_' . uniqid() . '_' . time();

        // Create a quick session with current date/time
        $session = AttendanceSession::create([
            'class_id' => $request->class_id,
            'teacher_id' => $teacher->id,
            'session_name' => 'Quick Session - ' . now()->format('M d, Y H:i'),
            'session_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'status' => 'active',
            'qr_code' => $qrCode
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quick session started successfully',
            'session' => $session,
            'redirect' => route('teacher.attendance.session', $session->id)
        ]);
    }

    /**
     * Get attendance session details
     */
    public function getAttendanceSession($sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        
        $session = DB::table('attendance_sessions')
                    ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                    ->where('attendance_sessions.id', $sessionId)
                    ->where('class_models.teacher_id', $teacher->id)
                    ->select(
                        'attendance_sessions.*',
                        'class_models.name as class_name',
                        'class_models.course',
                        'class_models.section'
                    )
                    ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found or access denied'], 404);
        }

        // Get attendance records for this session
        $records = DB::table('attendance_records')
                    ->join('students', 'attendance_records.student_id', '=', 'students.id')
                    ->where('attendance_session_id', $sessionId)
                    ->select(
                        'attendance_records.*',
                        'students.student_id',
                        'students.name',
                        'students.email'
                    )
                    ->get();

                // Get all students in the class for comparison
        $allStudents = DB::table('class_student')
                        ->join('students', 'class_student.student_id', '=', 'students.id')
                        ->where('class_student.class_model_id', $session->class_id)
                        ->select(
                            'students.id',
                            'students.student_id',
                            'students.name',
                            'students.email'
                        )
                        ->get();

        return response()->json([
            'success' => true,
            'session' => $session,
            'attendance_records' => $records,
            'all_students' => $allStudents
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
                'attendance_session_id' => $sessionId,
                'student_id' => $request->student_id
            ],
            [
                'status' => $request->status,
                'method' => $request->get('method'),
                'notes' => $request->notes,
                'marked_at' => in_array($request->status, ['present', 'late']) ? now() : null
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
     * Mark attendance via QR scan
     */
    public function markAttendanceByQR(Request $request, $sessionId)
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->where('status', 'active')
                                   ->findOrFail($sessionId);

        // Parse QR data to get student information
        try {
            $qrData = json_decode($request->qr_data, true);
            
            // Handle different QR formats
            if (isset($qrData['student_id'])) {
                $studentId = $qrData['student_id'];
            } elseif (isset($qrData['id'])) {
                $studentId = $qrData['id'];
            } else {
                // Try to parse as CSV format: "name,course,"
                $parts = explode(',', $request->qr_data);
                if (count($parts) >= 2) {
                    $name = trim($parts[0]);
                    $course = trim($parts[1]);
                    
                    // Look up student by name and course
                    $student = $this->findStudentByNameAndCourse($name, $course);
                    if (!$student) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Student not found: ' . $name
                        ], 404);
                    }
                    $studentId = $student->student_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR code format'
                    ], 400);
                }
            }

            // Find student in database
            $student = Student::where('student_id', $studentId)->first();
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found in database'
                ], 404);
            }

            // Check if student is enrolled in this class
            $isEnrolled = DB::table('class_student')
                           ->where('class_model_id', $session->class_id)
                           ->where('student_id', $student->id)
                           ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not enrolled in this class'
                ], 403);
            }

            // Check if already marked
            $existingRecord = AttendanceRecord::where('attendance_session_id', $sessionId)
                                            ->where('student_id', $student->id)
                                            ->first();

            if ($existingRecord) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student already marked as ' . $existingRecord->status,
                    'student' => $student,
                    'status' => $existingRecord->status
                ]);
            }

            // Mark attendance as present
            $record = AttendanceRecord::create([
                'attendance_session_id' => $sessionId,
                'student_id' => $student->id,
                'status' => 'present',
                'method' => 'qr_scan',
                'marked_at' => now()
            ]);

            // Update session counts
            $session->updateCounts();

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'student' => $student,
                'status' => 'present',
                'record' => $record
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to find student by name and course
     */
    private function findStudentByNameAndCourse($name, $course)
    {
        // Parse the input name to extract key parts
        $nameParts = array_filter(explode(' ', strtolower(trim($name))), function($part) {
            return !empty($part) && $part !== 'a.' && $part !== 'a'; // Filter out middle initial
        });

        // Search for student by matching key name parts and course
        $query = DB::table('students')->where('course', 'LIKE', '%' . $course . '%');
        
        // Add conditions for each significant name part
        foreach ($nameParts as $part) {
            $query->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $part . '%');
        }
        
        $student = $query->first();

        // If no exact match found, try a more flexible search
        if (!$student && count($nameParts) >= 2) {
            // Try matching just first and last name
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            
            $student = DB::table('students')
                        ->where('course', 'LIKE', '%' . $course . '%')
                        ->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $firstName . '%')
                        ->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $lastName . '%')
                        ->first();
        }

        return $student ? Student::find($student->id) : null;
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
     * Show attendance session page with QR scanner
     */
    public function showAttendanceSession($sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::with(['class', 'attendanceRecords.student'])
                                   ->where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        // Get all students in the class
        $allStudents = DB::table('class_student')
                        ->join('students', 'class_student.student_id', '=', 'students.id')
                        ->where('class_student.class_model_id', $session->class_id)
                        ->select(
                            'students.id',
                            'students.student_id',
                            'students.name',
                            'students.email',
                            'students.year',
                            'students.course',
                            'students.section'
                        )
                        ->get();

        // Get attendance records for this session
        $attendanceRecords = $session->attendanceRecords->pluck('student_id')->toArray();

        return Inertia::render('Teacher/AttendanceSession', [
            'teacher' => $teacher,
            'session' => $session,
            'students' => $allStudents,
            'attendance_records' => $attendanceRecords
        ]);
    }

    /**
     * Upload file to class
     */
    public function uploadFile(Request $request, $classId = null)
    {
        // Log the incoming request for debugging
        Log::info('Upload request received', [
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'class_id' => $request->input('class_id'),
            'all_input' => $request->all()
        ]);

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'class_id' => 'required|exists:class_models,id',
            'description' => 'nullable|string|max:500',
            'allow_download' => 'nullable|boolean',
            'notify_students' => 'nullable|boolean'
        ]);

        $teacher = $this->getCurrentTeacher();
        
        // Get class_id from request body
        $classId = $request->input('class_id');

        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

        $uploadedFiles = [];
        
        // Process each file
        foreach ($request->file('files') as $file) {
            $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('class-files/' . $classId, $fileName, 'public');

            $classFile = ClassFile::create([
                'class_id' => $classId,
                'teacher_id' => $teacher->id,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_url' => Storage::url($filePath),
                'description' => $request->description,
                'is_public' => $request->boolean('allow_download', true)
            ]);
            
            $uploadedFiles[] = $classFile;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
            'files' => $uploadedFiles
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
        $totalClasses = DB::table('class_models')
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
                                                  $studentCount = DB::table('class_student')
                                          ->where('class_model_id', $class->id)
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
                                                   ->selectRaw("
                                                       COUNT(*) as total_count,
                                                       SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count
                                                   ")
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
        $classes = ClassModel::forTeacher($teacher->id)
            ->select('id', 'name', 'course', 'section')
            ->get();

        return Inertia::render('Teacher/Files', [
            'teacher' => $teacher,
            'classes' => $classes
        ]);
    }

    /**
     * Display the reports page
     */
    public function reports()
    {
        $teacher = $this->getCurrentTeacher();

        // Get summary data for the reports dashboard
        $classes = DB::table('classes')
                    ->where('teacher_id', $teacher->id)
                    ->get();

        $totalStudents = 0;
        $attendanceData = [];

        foreach ($classes as $class) {
            $studentCount = DB::table('class_student')->where('class_model_id', $class->id)->count();
            $totalStudents += $studentCount;

            // Get recent attendance rate for this class
            $recentSessions = DB::table('attendance_sessions')
                               ->where('class_id', $class->id)
                               ->where('session_date', '>=', now()->subDays(7))
                               ->get();

            $totalPresent = 0;
            $totalPossible = 0;

            foreach ($recentSessions as $session) {
                $present = DB::table('attendance_records')
                            ->where('attendance_session_id', $session->id)
                            ->where('status', 'present')
                            ->count();
                $total = DB::table('attendance_records')
                          ->where('attendance_session_id', $session->id)
                          ->count();

                $totalPresent += $present;
                $totalPossible += $total;
            }

            $attendanceRate = $totalPossible > 0 ? ($totalPresent / $totalPossible) * 100 : 0;

            $attendanceData[] = [
                'class_name' => $class->name,
                'student_count' => $studentCount,
                'attendance_rate' => round($attendanceRate, 1)
            ];
        }

        return Inertia::render('Teacher/Reports', [
            'teacher' => $teacher,
            'summary' => [
                'total_classes' => count($classes),
                'total_students' => $totalStudents,
                'attendance_data' => $attendanceData
            ]
        ]);
    }

    /**
     * Get detailed attendance reports
     */
    public function attendanceReports(Request $request)
    {
        $teacher = $this->getCurrentTeacher();
        
        $classes = DB::table('classes')
                    ->where('teacher_id', $teacher->id)
                    ->get();

        $selectedClassId = $request->get('class_id');
        
        $query = DB::table('attendance_sessions')
                   ->join('classes', 'attendance_sessions.class_id', '=', 'classes.id')
                   ->where('classes.teacher_id', $teacher->id);

        if ($selectedClassId) {
            $query->where('attendance_sessions.class_id', $selectedClassId);
        }

        $sessions = $query->orderBy('attendance_sessions.session_date', 'desc')
                         ->select(
                             'attendance_sessions.*',
                             'classes.name as class_name',
                             'classes.course',
                             'classes.section'
                         )
                         ->get();

        // Get attendance records for each session
        foreach ($sessions as $session) {
            $records = DB::table('attendance_records')
                        ->join('students', 'attendance_records.student_id', '=', 'students.id')
                        ->where('attendance_session_id', $session->id)
                        ->select(
                            'students.student_id',
                            'students.name',
                            'attendance_records.status',
                            'attendance_records.marked_at'
                        )
                        ->get();

            $session->attendance_records = $records;
            $session->present_count = $records->where('status', 'present')->count();
            $session->absent_count = $records->where('status', 'absent')->count();
            $session->total_count = $records->count();
        }

        return Inertia::render('Teacher/AttendanceReports', [
            'teacher' => $teacher,
            'classes' => $classes,
            'sessions' => $sessions,
            'selectedClassId' => $selectedClassId
        ]);
    }

    /**
     * Export attendance report
     */
    public function exportAttendanceReport(Request $request)
    {
        $teacher = $this->getCurrentTeacher();
        
        // This would typically generate and return a CSV/PDF file
        // For now, return the data as JSON
        $classId = $request->get('class_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = DB::table('attendance_sessions')
                   ->join('classes', 'attendance_sessions.class_id', '=', 'classes.id')
                   ->where('classes.teacher_id', $teacher->id);

        if ($classId) {
            $query->where('attendance_sessions.class_id', $classId);
        }

        if ($startDate) {
            $query->where('attendance_sessions.session_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('attendance_sessions.session_date', '<=', $endDate);
        }

        $sessions = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Report exported successfully',
            'data' => $sessions
        ]);
    }

    /**
     * Student reports page
     */
    public function studentReports()
    {
        $teacher = $this->getCurrentTeacher();
        
        $classes = DB::table('classes')
                    ->where('teacher_id', $teacher->id)
                    ->get();

        $students = [];
        foreach ($classes as $class) {
            $classStudents = DB::table('class_student')
                              ->join('students', 'class_student.student_id', '=', 'students.id')
                              ->where('class_student.class_model_id', $class->id)
                              ->select(
                                  'students.*',
                                  'class_student.status',
                                  'class_student.enrolled_at'
                              )
                              ->get();

            foreach ($classStudents as $student) {
                // Calculate attendance rate for this student
                $totalSessions = DB::table('attendance_sessions')
                                  ->where('class_id', $class->id)
                                  ->count();

                $presentSessions = DB::table('attendance_records')
                                    ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                                    ->where('attendance_sessions.class_id', $class->id)
                                    ->where('attendance_records.student_id', $student->id)
                                    ->where('attendance_records.status', 'present')
                                    ->count();

                $attendanceRate = $totalSessions > 0 ? ($presentSessions / $totalSessions) * 100 : 0;

                $students[] = [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'class_name' => $class->name,
                    'attendance_rate' => round($attendanceRate, 1),
                    'total_sessions' => $totalSessions,
                    'present_sessions' => $presentSessions
                ];
            }
        }

        return Inertia::render('Teacher/StudentReports', [
            'teacher' => $teacher,
            'students' => $students,
            'classes' => $classes
        ]);
    }

    /**
     * Export reports page
     */
    public function exportReports()
    {
        $teacher = $this->getCurrentTeacher();
        
        $classes = DB::table('classes')
                    ->where('teacher_id', $teacher->id)
                    ->get();

        return Inertia::render('Teacher/ExportReports', [
            'teacher' => $teacher,
            'classes' => $classes
        ]);
    }

    /**
     * Lookup student by name for QR scanner
     */
    public function lookupStudent(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'course' => 'required|string'
        ]);

        $name = $request->input('name');
        $course = $request->input('course');

        // Parse the input name to extract key parts
        $nameParts = array_filter(explode(' ', strtolower(trim($name))), function($part) {
            return !empty($part) && $part !== 'a.' && $part !== 'a'; // Filter out middle initial
        });

        // Search for student by matching key name parts and course
        $query = DB::table('students')->where('course', 'LIKE', '%' . $course . '%');
        
        // Add conditions for each significant name part
        foreach ($nameParts as $part) {
            $query->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $part . '%');
        }
        
        $student = $query->first();

        // If no exact match found, try a more flexible search
        if (!$student && count($nameParts) >= 2) {
            // Try matching just first and last name
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            
            $student = DB::table('students')
                        ->where('course', 'LIKE', '%' . $course . '%')
                        ->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $firstName . '%')
                        ->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $lastName . '%')
                        ->first();
        }

        if ($student) {
            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'year' => $student->year,
                    'course' => $student->course,
                    'section' => $student->section,
                    'email' => $student->email
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
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

    /**
     * Show direct attendance page for quick QR scanning or manual input
     */
    public function showDirectAttendance($classId)
    {
        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->id)->findOrFail($classId);

        // Get all students in the class
        $students = DB::table('class_student')
                     ->join('students', 'class_student.student_id', '=', 'students.id')
                     ->where('class_student.class_model_id', $classId)
                     ->select(
                         'students.id',
                         'students.student_id',
                         'students.name',
                         'students.email',
                         'students.year',
                         'students.course',
                         'students.section'
                     )
                     ->get();

        // Create or get active session for today
        $activeSession = AttendanceSession::where('class_id', $classId)
                                         ->where('teacher_id', $teacher->id)
                                         ->where('session_date', now()->toDateString())
                                         ->where('status', 'active')
                                         ->first();

        if (!$activeSession) {
            // Create a new session for today
            $qrCode = 'session_' . uniqid() . '_' . time();
            $activeSession = AttendanceSession::create([
                'class_id' => $classId,
                'teacher_id' => $teacher->id,
                'session_name' => 'Direct Attendance - ' . now()->format('M d, Y H:i'),
                'session_date' => now()->toDateString(),
                'start_time' => now()->format('H:i:s'),
                'status' => 'active',
                'qr_code' => $qrCode
            ]);
        }

        // Get attendance records for this session
        $attendanceRecords = DB::table('attendance_records')
                              ->where('attendance_session_id', $activeSession->id)
                              ->pluck('student_id')
                              ->toArray();

        return Inertia::render('Teacher/DirectAttendance', [
            'teacher' => $teacher,
            'classInfo' => [
                'id' => $class->id,
                'name' => $class->name,
                'course' => $class->course,
                'section' => $class->section,
                'year' => $class->year,
                'class_code' => $class->class_code
            ],
            'session' => $activeSession,
            'students' => $students,
            'attendance_records' => $attendanceRecords
        ]);
    }

    /**
     * Generate a unique class code
     */
    private function generateUniqueClassCode($course, $section, $year)
    {
        $baseCode = strtoupper(substr($course, 0, 3) . '-' . $section . '-' . $year);
        $classCode = $baseCode;
        $counter = 1;

        // Keep generating until we find a unique code
        while (ClassModel::where('class_code', $classCode)->exists()) {
            $classCode = $baseCode . '-' . $counter;
            $counter++;
        }

        return $classCode;
    }
}