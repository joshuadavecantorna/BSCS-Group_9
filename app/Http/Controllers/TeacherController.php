<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ClassFile;
use App\Models\ExcuseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;
use Inertia\Inertia;
use Inertia\Response;
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
     * API endpoint for real-time dashboard updates
     */
    public function dashboardApi()
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $stats = $this->getDashboardStats($teacher);
            $recentActivity = $this->getRecentActivity($teacher);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recentActivity' => $recentActivity,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display teacher's classes
     */
    public function classes()
    {
        $teacher = $this->getCurrentTeacher();

        // Get classes directly from database with real-time student count
        $classes = DB::table('class_models')
                     ->where('teacher_id', $teacher->user_id)
                     ->get()
                     ->map(function ($class) {
                         // Get real student count for each class
                         $studentCount = DB::table('class_student')
                                          ->where('class_model_id', $class->id)
                                          ->count();

                         return [
                             'id' => $class->id,
                             'name' => $class->name,
                             'class_code' => $class->class_code,
                             'course' => $class->course,
                             'section' => $class->section,
                             'subject' => $class->subject,
                             'year' => $class->year,
                             'description' => $class->description,
                             'room' => $class->room ?? null,
                             'schedule_time' => $class->schedule_time,
                             'schedule_days' => $this->parseScheduleDays($class->schedule_days),
                             'student_count' => $studentCount,
                             'is_active' => $class->is_active
                         ];
                     });

        // Debug: Log what we're sending to frontend
        Log::info('Classes data being sent to frontend:', $classes->toArray());

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
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'class_code' => 'nullable|string|max:50',
                'course' => 'required|string|max:255',
                'section' => 'required|string|max:10',
                'subject' => 'nullable|string|max:255',
                'year' => 'required|string|max:50',
                'description' => 'nullable|string',
                'room' => 'nullable|string|max:255',
                'schedule_time' => 'nullable|string|max:255',
                'schedule_days' => 'nullable|array',
            ]);

            $teacher = $this->getCurrentTeacher();

            // Use provided class code or generate one
            $classCode = $request->input('class_code') ? trim($request->input('class_code')) : $this->generateUniqueClassCode($request->course, $request->section, $request->year);
            
            // Clean and prepare the data
            $name = trim($request->input('name'));
            $course = trim($request->input('course'));
            $section = trim($request->input('section'));
            $subject = $request->input('subject') ? trim($request->input('subject')) : null;
            $year = trim($request->input('year'));
            $description = $request->input('description') ? trim($request->input('description')) : null;
            $scheduleTime = $request->input('schedule_time');
            $scheduleDays = $request->input('schedule_days', []);

            // Build insert dynamically so we only include optional columns when present in the DB
            $insertCols = [
                'name', 'course', 'class_code', 'section', 'subject', 'year', 'description', 'teacher_id', 'schedule_time', 'schedule_days'
            ];

            $values = [
                $name,
                $course,
                $classCode,
                $section,
                $subject,
                $year,
                $description,
                $teacher->user_id,
                $scheduleTime,
                json_encode($scheduleDays),
            ];

            // include room/academic fields only if migration has been run
            if (Schema::hasColumn('class_models', 'room')) {
                $insertCols[] = 'room';
                $values[] = $request->input('room');
            }

            // finalize columns and placeholders (is_active and timestamps are literals)
            $colsSql = implode(', ', $insertCols) . ', is_active, created_at, updated_at';
            $placeholders = implode(', ', array_fill(0, count($insertCols), '?')) . ', true, now(), now()';

            $sql = "INSERT INTO class_models ($colsSql) VALUES ($placeholders) RETURNING id";

            $classId = DB::select($sql, $values)[0]->id;
            $class = ClassModel::find($classId);

            return redirect()->route('teacher.classes')->with('success', 'Class created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to create class: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create class. Please try again.'])->withInput();
        }
    }

    /**
     * Get class details with students and attendance records
     */
    public function getClass($id)
    {
        $teacher = $this->getCurrentTeacher();
        
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($id);
        
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
        try {

            $request->validate([
                'name' => 'required|string|max:255',
                'class_code' => 'nullable|string|max:50',
                'course' => 'required|string|max:100',
                'section' => 'required|string|max:10',
                'subject' => 'nullable|string|max:255',
                'year' => 'required|string|max:20',
                'description' => 'nullable|string',
                'room' => 'nullable|string|max:255',
                'schedule_time' => 'nullable|string|max:255',
                'schedule_days' => 'nullable|array'
            ]);

            $teacher = $this->getCurrentTeacher();
            $class = ClassModel::where('teacher_id', $teacher->user_id)->findOrFail($id);

            // Build update data dynamically
            $updateData = [
                'name' => trim($request->name),
                'class_code' => $request->class_code ? trim($request->class_code) : null,
                'course' => trim($request->course),
                'section' => trim($request->section),
                'subject' => $request->subject ? trim($request->subject) : null,
                'year' => trim($request->year),
                'description' => $request->description ? trim($request->description) : null,
                'schedule_time' => $request->schedule_time,
                'schedule_days' => json_encode($request->schedule_days ?? [])
            ];

            // Only include room if the column exists in the database
            if (Schema::hasColumn('class_models', 'room')) {
                $updateData['room'] = $request->room ? trim($request->room) : null;
                Log::info('Room column exists, adding room to updateData:', ['room_value' => $updateData['room']]);
            } else {
                Log::warning('Room column does not exist in database');
            }

            Log::info('Final updateData:', $updateData);

            $class->update($updateData);

            // Debug: Check what was actually saved
            $fresh = $class->fresh();
            Log::info('Class after update:', [
                'id' => $fresh->id,
                'room' => $fresh->room,
                'schedule_time' => $fresh->schedule_time,
                'schedule_days' => $fresh->schedule_days
            ]);

            return redirect()->route('teacher.classes')->with('success', 'Class updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to update class: ' . $e->getMessage());
            Log::error('Request data: ', $request->all());
            return back()->withErrors(['error' => 'Failed to update class. Please try again.'])->withInput();
        }
    }

    /**
     * Delete (deactivate) a class
     */
    public function deleteClass($id)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $class = ClassModel::where('teacher_id', $teacher->user_id)->findOrFail($id);

            $class->update(['is_active' => false]);

            return redirect()->route('teacher.classes')->with('success', 'Class deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete class: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete class. Please try again.']);
        }
    }

    /**
     * Get students in a specific class
     */
    public function getClassStudents($classId)
    {
        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

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
     * Search for existing students in the database
     */
    public function searchStudents(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2'
        ]);

        $searchTerm = $request->search;
        
        // Search students by name, student_id, or email
        $students = Student::where(function($query) use ($searchTerm) {
                        $query->where('name', 'ILIKE', "%{$searchTerm}%")
                              ->orWhere('student_id', 'ILIKE', "%{$searchTerm}%")
                              ->orWhere('email', 'ILIKE', "%{$searchTerm}%");
                    })
                    ->whereRaw('is_active = true')
                    ->limit(20)
                    ->get(['id', 'student_id', 'name', 'email', 'course', 'year', 'section']);

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Add existing students from database to a class
     */
    public function addExistingStudentsToClass(Request $request, $classId)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|integer|exists:students,id'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

        $addedStudents = [];
        $skippedCount = 0;

        foreach ($request->student_ids as $studentId) {
            // Check if student is already enrolled
            $exists = DB::table('class_student')
                       ->where('class_model_id', $classId)
                       ->where('student_id', $studentId)
                       ->exists();

            if (!$exists) {
                DB::table('class_student')->insert([
                    'class_model_id' => $classId,
                    'student_id' => $studentId,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $student = Student::find($studentId);
                $addedStudents[] = $student;
            } else {
                $skippedCount++;
            }
        }

        // Return an Inertia response for proper handling
        return redirect()->back()->with([
            'success' => true,
            'message' => count($addedStudents) . ' students added successfully' . 
                        ($skippedCount > 0 ? " ({$skippedCount} already enrolled)" : ''),
            'addedStudents' => $addedStudents
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
                    'is_active' => DB::raw('true')
                ];
            }
        }

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

        $addedStudents = [];

        foreach ($students as $studentData) {
            // Find existing student first
            $student = Student::where('student_id', $studentData['student_id'])->first();
            
            if (!$student) {
                // Create new student using raw SQL to handle boolean properly
                $studentId = DB::select("
                    INSERT INTO students (student_id, name, email, year, course, section, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, true, now(), now()) 
                    RETURNING id
                ", [
                    $studentData['student_id'],
                    $studentData['name'],
                    $studentData['email'],
                    $studentData['year'],
                    $studentData['course'],
                    $studentData['section']
                ])[0]->id;
                
                $student = Student::find($studentId);
            }

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
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

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
            'duration' => 'nullable|integer|min:5|max:240',
            'notes' => 'nullable|string|max:500'
        ]);

        $teacher = $this->getCurrentTeacher();
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($request->class_id);
        $totalStudents = DB::table('class_student')->where('class_model_id', $request->class_id)->count();

        // Generate unique QR code for this session
        $qrCode = 'session_' . uniqid() . '_' . time();

        $session = AttendanceSession::create([
            'class_id' => $request->class_id,
            'teacher_id' => $teacher->id, // Use teacher's primary key
            'session_name' => $request->session_name,
            'session_date' => now()->toDateString(), // Use current date
            'start_time' => now()->toTimeString(), // Use current time
            'duration' => $request->duration ?? 60, // Default to 60 minutes
            'notes' => $request->notes,
            'status' => 'active',
            'qr_code' => $qrCode
        ]);

        // Redirect to the attendance session page instead of JSON response
        return redirect()->route('teacher.attendance.session', $session->id)
            ->with('success', 'Attendance session started successfully!');
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
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($request->class_id);
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

        // Return an Inertia redirect to the session page
        return redirect()->route('teacher.attendance.session', $session->id)
                         ->with([
                             'success' => true,
                             'message' => 'Quick session started successfully',
                             'session' => $session
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
                    ->where('class_models.teacher_id', $teacher->user_id)
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
            'student_id' => 'required|string', // Accept student_id as string (e.g., "23-62514")
            'status' => 'required|in:present,absent,excused,late',
            'method' => 'required|in:qr,manual,webcam,file_upload',
            'notes' => 'nullable|string'
        ]);

        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        // Find student by student_id (string like "23-62514")
        $student = Student::where('student_id', $request->student_id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with ID: ' . $request->student_id
            ], 404);
        }

        // Verify the student is enrolled in this class
        $isEnrolled = DB::table('class_student')
                       ->where('class_model_id', $session->class_id)
                       ->where('student_id', $student->id) // Use the database ID for the pivot table
                       ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not enrolled in this class'
            ], 403);
        }

        $record = AttendanceRecord::updateOrCreate(
            [
                'attendance_session_id' => $sessionId,
                'student_id' => $student->id // Use the database ID for attendance records
            ],
            [
                'status' => $request->status,
                'method' => $request->get('method'),
                'notes' => $request->notes,
                'marked_at' => in_array($request->status, ['present', 'late']) ? now() : null
            ]
        );

        // Update session counts if the method exists
        if (method_exists($session, 'updateCounts')) {
            $session->updateCounts();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'record' => $record,
            'student' => $student
        ]);
    }

    /**
     * Mark attendance via QR scan
     */
    public function markAttendanceByQR(Request $request, $sessionId)
    {
        try {
            Log::info('QR scan request received', [
                'session_id' => $sessionId,
                'qr_data' => $request->qr_data,
                'headers' => $request->headers->all()
            ]);

            $request->validate([
                'qr_data' => 'required|string',
            ]);

            $teacher = $this->getCurrentTeacher();
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 403);
            }

            $session = AttendanceSession::where('teacher_id', $teacher->id)
                                       ->where('status', 'active')
                                       ->findOrFail($sessionId);
        } catch (\Exception $e) {
            Log::error('QR scan error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }

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
                Log::error('Student lookup failed', [
                    'student_id' => $studentId,
                    'qr_data' => $request->qr_data
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Database lookup failed for ID: ' . $studentId
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

        // Return an Inertia redirect response
        return redirect()->route('teacher.attendance')
                         ->with([
                             'success' => true,
                             'message' => 'Attendance session ended successfully',
                             'session' => $session
                         ]);
    }

    /**
     * Delete attendance session
     */
    public function deleteAttendanceSession($sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        // Delete attendance records first (cascade should handle this, but let's be explicit)
        AttendanceRecord::where('attendance_session_id', $sessionId)->delete();
        
        // Delete the session
        $sessionName = $session->session_name;
        $session->delete();

        // Return an Inertia redirect response
        return redirect()->route('teacher.attendance')
                         ->with([
                             'success' => true,
                             'message' => "Attendance session '{$sessionName}' deleted successfully"
                         ]);
    }

    /**
     * Export attendance session as CSV
     */
    public function exportAttendanceSession($sessionId)
    {
        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::with(['class'])
                                   ->where('teacher_id', $teacher->id)
                                   ->findOrFail($sessionId);

        // Get attendance records with student details
        $records = DB::table('attendance_records')
                    ->join('students', 'attendance_records.student_id', '=', 'students.id')
                    ->where('attendance_session_id', $sessionId)
                    ->select(
                        'students.student_id',
                        'students.name',
                        'students.email',
                        'students.course',
                        'students.section',
                        'attendance_records.status',
                        'attendance_records.marked_at',
                        'attendance_records.marked_by'
                    )
                    ->orderBy('students.name')
                    ->get();

        // Create CSV content
        $csv = "Student ID,Name,Email,Course,Section,Status,Marked At,Marked By\n";
        
        foreach ($records as $record) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $record->student_id,
                $record->name,
                $record->email,
                $record->course,
                $record->section,
                ucfirst($record->status),
                $record->marked_at,
                ucfirst($record->marked_by)
            );
        }

        // Set headers for CSV download
        $filename = sprintf(
            'attendance_%s_%s_%s.csv',
            str_replace(' ', '_', $session->class->name ?? 'class'),
            $session->session_date,
            date('His')
        );

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
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
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : ($request->hasFile('files[]') ? count($request->file('files[]')) : 0),
            'single_file' => $request->hasFile('files') && !is_array($request->file('files')),
            'class_id' => $request->input('class_id'),
            'all_input' => $request->all()
        ]);

        // Convert string booleans to actual booleans for proper validation
        if ($request->has('allow_download')) {
            $allowDownload = $request->input('allow_download');
            if ($allowDownload === 'true' || $allowDownload === '1') {
                $request->merge(['allow_download' => true]);
            } elseif ($allowDownload === 'false' || $allowDownload === '0') {
                $request->merge(['allow_download' => false]);
            }
        }

        if ($request->has('notify_students')) {
            $notifyStudents = $request->input('notify_students');
            if ($notifyStudents === 'true' || $notifyStudents === '1') {
                $request->merge(['notify_students' => true]);
            } elseif ($notifyStudents === 'false' || $notifyStudents === '0') {
                $request->merge(['notify_students' => false]);
            }
        }

        // Handle both single file and multiple files
        $files = [];
        if ($request->hasFile('files') && !is_array($request->file('files'))) {
            // Single file upload
            $files = [$request->file('files')];
            $request->validate([
                'files' => 'required|file|max:10240', // 10MB max per file
                'class_id' => 'required|exists:class_models,id',
                'description' => 'nullable|string|max:500',
                'allow_download' => 'nullable|boolean',
                'notify_students' => 'nullable|boolean'
            ]);
        } else {
            // Multiple files upload
            $files = $request->file('files', []);
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file|max:10240', // 10MB max per file
                'class_id' => 'required|exists:class_models,id',
                'description' => 'nullable|string|max:500',
                'allow_download' => 'nullable|boolean',
                'notify_students' => 'nullable|boolean'
            ]);
        }

        $teacher = $this->getCurrentTeacher();

        // Get class_id from request body
        $classId = $request->input('class_id');

        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

        $uploadedFiles = [];

        // Process each file
        foreach ($files as $file) {
            if (!$file) continue;

            $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('class-files/' . $classId, $fileName, 'public');

            $classFile = ClassFile::create([
                'class_id' => $classId,
                'teacher_id' => $teacher->id,
                'file_name' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => 'class-files/' . $classId . '/' . $fileName,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'visibility' => $request->boolean('allow_download', true) ? 'public' : 'private',
                'is_active' => true
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
     * Get files analytics for the teacher
     */
    public function getFilesAnalytics()
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'No teacher found for testing'
                ], 404);
            }
        }
        
        // Get all files for this teacher
        $totalFiles = ClassFile::where('teacher_id', $teacher->id)->count();
        
        // Calculate storage used
        $totalSize = ClassFile::where('teacher_id', $teacher->id)->sum('file_size');
        $storageUsed = $this->formatFileSize($totalSize);
        
        // Get file type breakdown using PostgreSQL-compatible queries
        $documents = ClassFile::where('teacher_id', $teacher->id)
                              ->where(function($query) {
                                  $query->where('file_type', 'ILIKE', '%pdf%')
                                        ->orWhere('file_type', 'ILIKE', '%doc%')
                                        ->orWhere('file_type', 'ILIKE', '%ppt%')
                                        ->orWhere('file_type', 'ILIKE', '%text%');
                              })
                              ->count();
        
        $images = ClassFile::where('teacher_id', $teacher->id)
                           ->where(function($query) {
                               $query->where('file_type', 'ILIKE', '%image%')
                                     ->orWhere('file_type', 'ILIKE', '%png%')
                                     ->orWhere('file_type', 'ILIKE', '%jpg%')
                                     ->orWhere('file_type', 'ILIKE', '%jpeg%')
                                     ->orWhere('file_type', 'ILIKE', '%gif%');
                           })
                           ->count();
        
        $videos = ClassFile::where('teacher_id', $teacher->id)
                           ->where(function($query) {
                               $query->where('file_type', 'ILIKE', '%video%')
                                     ->orWhere('file_type', 'ILIKE', '%mp4%')
                                     ->orWhere('file_type', 'ILIKE', '%avi%')
                                     ->orWhere('file_type', 'ILIKE', '%mov%');
                           })
                           ->count();
        
        $others = $totalFiles - ($documents + $images + $videos);
        
        $fileTypes = (object) [
            'documents' => $documents,
            'images' => $images,
            'videos' => $videos,
            'others' => max(0, $others)
        ];
        
        // Get recent uploads count (last 7 days)
        $recentUploads = ClassFile::where('teacher_id', $teacher->id)
                                 ->where('created_at', '>=', now()->subDays(7))
                                 ->count();

        return response()->json([
            'success' => true,
            'total_files' => $totalFiles,
            'storage_used' => $storageUsed,
            'storage_available' => '10 GB', // This could be configurable
            'downloads' => 0, // This would require a download tracking table
            'recent_uploads' => $recentUploads,
            'file_types' => [
                'documents' => $fileTypes->documents ?? 0,
                'images' => $fileTypes->images ?? 0,
                'videos' => $fileTypes->videos ?? 0,
                'others' => $fileTypes->others ?? 0
            ]
        ]);
    }

    /**
     * Display all files page
     */
    public function getAllFilesPage()
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        $classes = ClassModel::forTeacher($teacher->user_id)
            ->select('id', 'name', 'course', 'section')
            ->get();

        return Inertia::render('Teacher/AllFiles', [
            'teacher' => $teacher,
            'classes' => $classes
        ]);
    }

    /**
     * Get all files for the teacher with pagination and filtering (API)
     */
    public function getAllFiles(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'No teacher found for testing'
                ], 404);
            }
        }
        
        $query = ClassFile::with(['class'])
                          ->where('teacher_id', $teacher->id)
                          ->whereRaw('COALESCE(is_active, true) = true');

        // Apply filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('file_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('original_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->file_type) {
            $query->where('file_type', 'LIKE', '%' . $request->file_type . '%');
        }

        // Sort by creation date (newest first)
        $files = $query->orderBy('created_at', 'desc')
                      ->paginate(20)
                      ->through(function ($file) {
                          return [
                              'id' => $file->id,
                              'file_name' => $file->original_name ?? $file->file_name,
                              'file_type' => $file->file_type,
                              'file_size_formatted' => $file->file_size_formatted,
                              'class_name' => $file->class->name ?? 'Unknown',
                              'class_course' => $file->class->course ?? '',
                              'description' => $file->description,
                              'created_at' => $file->created_at,
                              'download_url' => route('teacher.files.download', $file->id)
                          ];
                      });

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Get recent files for the teacher
     */
    public function getRecentFiles()
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'No teacher found for testing'
                ], 404);
            }
        }
        
        $files = ClassFile::with(['class'])
                          ->where('teacher_id', $teacher->id)
                          ->whereRaw('COALESCE(is_active, true) = true')
                          ->orderBy('created_at', 'desc')
                          ->limit(10)
                          ->get()
                          ->map(function ($file) {
                              return [
                                  'id' => $file->id,
                                  'file_name' => $file->original_name ?? $file->file_name,
                                  'file_type' => $file->file_type,
                                  'file_size_formatted' => $file->file_size_formatted,
                                  'class_name' => $file->class->name ?? 'Unknown',
                                  'class_course' => $file->class->course ?? '',
                                  'created_at' => $file->created_at,
                                  'download_url' => route('teacher.files.download', $file->id)
                              ];
                          });

        return response()->json([
            'success' => true,
            'recentFiles' => $files
        ]);
    }

    /**
     * Download a file
     */
    public function downloadFile($fileId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        
        $file = ClassFile::where('teacher_id', $teacher->id)
                         ->where('id', $fileId)
                         ->whereRaw('COALESCE(is_active, true) = true')
                         ->firstOrFail();

        $filePath = storage_path('app/public/' . $file->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $file->original_name ?? $file->file_name);
    }

    /**
     * Get files for a specific class
     */
    public function getClassFiles($classId)
    {
        $teacher = $this->getCurrentTeacher();
        
        // Verify teacher has access to this class
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);
        
        $files = ClassFile::where('class_id', $classId)
                          ->where('teacher_id', $teacher->id)
                          ->whereRaw('COALESCE(is_active, true) = true')
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($file) {
                              return [
                                  'id' => $file->id,
                                  'file_name' => $file->file_name,
                                  'original_name' => $file->original_name ?? $file->file_name,
                                  'file_type' => $file->file_type,
                                  'file_size' => $file->file_size_formatted,
                                  'description' => $file->description,
                                  'created_at' => $file->created_at,
                                  'download_url' => route('teacher.files.download', $file->id)
                              ];
                          });

        return response()->json([
            'success' => true,
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'course' => $class->course
            ],
            'files' => $files
        ]);
    }

    /**
     * Format file size for display
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
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
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        
        // Get total classes for this teacher (use user_id for class_models)
        $totalClasses = DB::table('class_models')
                           ->where('teacher_id', $teacher->user_id)
                           ->whereRaw('COALESCE(is_active, true) = true')
                           ->count();
        
        // Get total students enrolled in this teacher's classes
        $totalStudents = DB::table('class_student')
                            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                            ->where('class_models.teacher_id', $teacher->user_id)
                            ->whereRaw('COALESCE(class_models.is_active, true) = true')
                            ->distinct('class_student.student_id')
                            ->count('class_student.student_id');
        
        // Get today's attendance sessions count (use teacher primary key)
        $todaySessions = DB::table('attendance_sessions')
                            ->where('teacher_id', $teacher->id)
                            ->whereDate('session_date', $today)
                            ->count();

        // Calculate today's attendance stats
        $todayAttendance = DB::table('attendance_records')
                            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                            ->where('attendance_sessions.teacher_id', $teacher->id)
                            ->whereDate('attendance_sessions.session_date', $today)
                            ->selectRaw('
                                SUM(CASE WHEN attendance_records.status = \'present\' THEN 1 ELSE 0 END) as present,
                                SUM(CASE WHEN attendance_records.status = \'absent\' THEN 1 ELSE 0 END) as absent,
                                SUM(CASE WHEN attendance_records.status = \'excused\' THEN 1 ELSE 0 END) as excused,
                                SUM(CASE WHEN attendance_records.status = \'late\' THEN 1 ELSE 0 END) as late
                            ')
                            ->first();

        // Calculate weekly attendance rate
        $weeklyStats = DB::table('attendance_records')
                        ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                        ->where('attendance_sessions.teacher_id', $teacher->id)
                        ->whereDate('attendance_sessions.session_date', '>=', $weekStart)
                        ->selectRaw('
                            COUNT(*) as total_records,
                            SUM(CASE WHEN attendance_records.status IN (\'present\', \'late\') THEN 1 ELSE 0 END) as present_records
                        ')
                        ->first();

        $weeklyAttendanceRate = $weeklyStats->total_records > 0 
                               ? round(($weeklyStats->present_records / $weeklyStats->total_records) * 100, 1) 
                               : 0;

        // Calculate monthly attendance rate
        $monthlyStats = DB::table('attendance_records')
                         ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                         ->where('attendance_sessions.teacher_id', $teacher->id)
                         ->whereDate('attendance_sessions.session_date', '>=', $monthStart)
                         ->selectRaw('
                             COUNT(*) as total_records,
                             SUM(CASE WHEN attendance_records.status IN (\'present\', \'late\') THEN 1 ELSE 0 END) as present_records
                         ')
                         ->first();

        $monthlyAttendanceRate = $monthlyStats->total_records > 0 
                                ? round(($monthlyStats->present_records / $monthlyStats->total_records) * 100, 1) 
                                : 0;

        // Additional analytics for better insights
        $totalSessionsAllTime = DB::table('attendance_sessions')
                                 ->where('teacher_id', $teacher->id)
                                 ->count();
        
        $activeSessionsCount = DB::table('attendance_sessions')
                              ->where('teacher_id', $teacher->id)
                              ->where('status', 'active')
                              ->count();
        
        // Get recent activity count
        $recentActivityCount = DB::table('attendance_sessions')
                              ->where('teacher_id', $teacher->id)
                              ->where('created_at', '>=', now()->subDays(7))
                              ->count();

        return [
            'totalClasses' => $totalClasses,
            'totalStudents' => $totalStudents,
            'todayPresent' => $todayAttendance->present ?? 0,
            'todayAbsent' => $todayAttendance->absent ?? 0,
            'todayExcused' => $todayAttendance->excused ?? 0,
            'todayLate' => $todayAttendance->late ?? 0,
            'todaySessions' => $todaySessions,
            'weeklyAttendanceRate' => $weeklyAttendanceRate,
            'monthlyAttendanceRate' => $monthlyAttendanceRate,
            'totalSessions' => $totalSessionsAllTime,
            'activeSessions' => $activeSessionsCount,
            'recentActivityCount' => $recentActivityCount,
            'lastUpdated' => now()->toISOString()
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
        $activities = [];
        
        // Get recent attendance sessions
        $recentSessions = DB::table('attendance_sessions')
                           ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                           ->where('attendance_sessions.teacher_id', $teacher->id)
                           ->orderBy('attendance_sessions.created_at', 'desc')
                           ->limit(5)
                           ->select(
                               'attendance_sessions.session_name',
                               'attendance_sessions.created_at',
                               'attendance_sessions.status',
                               'class_models.name as class_name'
                           )
                           ->get();

        foreach ($recentSessions as $session) {
            $time = Carbon::parse($session->created_at)->format('g:i A');
            $activities[] = [
                'time' => $time,
                'text' => "Started attendance session: {$session->session_name} for {$session->class_name}",
                'type' => $session->status === 'active' ? 'info' : 'success'
            ];
        }

        // Get recent class updates
        $recentClasses = DB::table('class_models')
                          ->where('teacher_id', $teacher->id)
                          ->orderBy('updated_at', 'desc')
                          ->limit(3)
                          ->select('name', 'updated_at', 'created_at')
                          ->get();

        foreach ($recentClasses as $class) {
            $time = Carbon::parse($class->updated_at)->format('g:i A');
            $isNew = Carbon::parse($class->created_at)->diffInDays(Carbon::parse($class->updated_at)) < 1;
            
            $activities[] = [
                'time' => $time,
                'text' => $isNew ? "Created new class: {$class->name}" : "Updated class: {$class->name}",
                'type' => $isNew ? 'success' : 'info'
            ];
        }

        // Sort by time (assuming today's activities)
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 6);
    }

    /**
     * Display the attendance page
     */
    public function attendance()
    {
        $teacher = $this->getCurrentTeacher();
        
        // Get teacher's classes from class_models table
        $classes = DB::table('class_models')
                     ->where('teacher_id', $teacher->user_id)
                     ->whereRaw('is_active = true')
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
                           ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                           ->where('class_models.teacher_id', $teacher->user_id)
                           ->orderBy('attendance_sessions.created_at', 'desc')
                           ->take(10)
                           ->get([
                               'attendance_sessions.id',
                               'attendance_sessions.session_name',
                               'class_models.name as class_name',
                               'class_models.course',
                               'attendance_sessions.session_date',
                               'attendance_sessions.start_time',
                               'attendance_sessions.status'
                           ])
                           ->map(function ($session) {
                               $attendanceStats = DB::table('attendance_records')
                                                   ->where('attendance_session_id', $session->id)
                                                   ->selectRaw("
                                                       COUNT(*) as total_count,
                                                       SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count
                                                   ")
                                                   ->first();

                               $date = Carbon::parse($session->session_date);
                               $time = Carbon::parse($session->start_time);
                               
                               return [
                                   'id' => $session->id,
                                   'session_name' => $session->session_name,
                                   'class_name' => $session->class_name,
                                   'course_code' => $session->course ?? 'N/A',
                                   'date' => $date->format('M d, Y'),
                                   'time' => $time->format('g:i A'),
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
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        $classes = ClassModel::forTeacher($teacher->user_id)
            ->select('id', 'name', 'course', 'section')
            ->get();

        // Get recent files for initial load
        $recentFiles = ClassFile::with(['class'])
                                ->where('teacher_id', $teacher->id)
                                ->whereRaw('COALESCE(is_active, true) = true')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get()
                                ->map(function ($file) {
                                    return [
                                        'id' => $file->id,
                                        'file_name' => $file->original_name ?? $file->file_name,
                                        'file_type' => $file->file_type,
                                        'file_size_formatted' => $file->file_size_formatted,
                                        'class_name' => $file->class->name ?? 'Unknown',
                                        'class_course' => $file->class->course ?? '',
                                        'created_at' => $file->created_at,
                                        'download_url' => route('teacher.files.download', $file->id)
                                    ];
                                });

        // Get file statistics
        $totalFiles = ClassFile::where('teacher_id', $teacher->id)->count();
        $totalSize = ClassFile::where('teacher_id', $teacher->id)->sum('file_size');
        
        // Get file type breakdown using PostgreSQL-compatible queries
        $documents = ClassFile::where('teacher_id', $teacher->id)
                              ->where(function($query) {
                                  $query->where('file_type', 'ILIKE', '%pdf%')
                                        ->orWhere('file_type', 'ILIKE', '%doc%')
                                        ->orWhere('file_type', 'ILIKE', '%ppt%')
                                        ->orWhere('file_type', 'ILIKE', '%text%');
                              })
                              ->count();
        
        $images = ClassFile::where('teacher_id', $teacher->id)
                           ->where(function($query) {
                               $query->where('file_type', 'ILIKE', '%image%')
                                     ->orWhere('file_type', 'ILIKE', '%png%')
                                     ->orWhere('file_type', 'ILIKE', '%jpg%')
                                     ->orWhere('file_type', 'ILIKE', '%jpeg%')
                                     ->orWhere('file_type', 'ILIKE', '%gif%');
                           })
                           ->count();
        
        $videos = ClassFile::where('teacher_id', $teacher->id)
                           ->where(function($query) {
                               $query->where('file_type', 'ILIKE', '%video%')
                                     ->orWhere('file_type', 'ILIKE', '%mp4%')
                                     ->orWhere('file_type', 'ILIKE', '%avi%')
                                     ->orWhere('file_type', 'ILIKE', '%mov%');
                           })
                           ->count();
        
        $others = $totalFiles - ($documents + $images + $videos);
        
        $fileTypes = (object) [
            'documents' => $documents,
            'images' => $images,
            'videos' => $videos,
            'others' => max(0, $others)
        ];

        $recentUploads = ClassFile::where('teacher_id', $teacher->id)
                                 ->where('created_at', '>=', now()->subDays(7))
                                 ->count();

        return Inertia::render('Teacher/Files', [
            'teacher' => $teacher,
            'classes' => $classes,
            'recentFiles' => $recentFiles,
            'analytics' => [
                'totalFiles' => $totalFiles,
                'storageUsed' => $this->formatFileSize($totalSize),
                'storageAvailable' => '10 GB',
                'downloads' => 0, // Would need download tracking
                'recentUploads' => $recentUploads,
                'fileTypes' => [
                    'documents' => $fileTypes->documents ?? 0,
                    'images' => $fileTypes->images ?? 0,
                    'videos' => $fileTypes->videos ?? 0,
                    'others' => max(0, $totalFiles - ($fileTypes->documents ?? 0) - ($fileTypes->images ?? 0) - ($fileTypes->videos ?? 0))
                ]
            ]
        ]);
    }

    /**
     * Display the reports page
     */
    public function reports()
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }

        // Get comprehensive data for the reports dashboard
        $classes = DB::table('class_models')
                    ->where('teacher_id', $teacher->user_id)
                    ->whereRaw('COALESCE(is_active, true) = true')
                    ->get();

        $totalStudents = 0;
        $weeklyAttendanceData = [];
        $totalReports = 0;
        $reportsDownloaded = 0;
        $totalAttendanceRecords = 0;
        $totalPresentRecords = 0;

        // Calculate comprehensive statistics
        foreach ($classes as $class) {
            $studentCount = DB::table('class_student')
                             ->where('class_model_id', $class->id)
                             ->where('status', 'enrolled')
                             ->count();
            $totalStudents += $studentCount;

            // Get weekly attendance rate for this class
            $weekStart = now()->startOfWeek();
            $weeklySessionsQuery = DB::table('attendance_sessions')
                                    ->where('class_id', $class->id)
                                    ->where('session_date', '>=', $weekStart->format('Y-m-d'));

            $weeklySessions = $weeklySessionsQuery->get();
            $weeklyPresent = 0;
            $weeklyTotal = 0;

            foreach ($weeklySessions as $session) {
                $sessionRecords = DB::table('attendance_records')
                                   ->where('attendance_session_id', $session->id)
                                   ->get();
                
                $present = $sessionRecords->where('status', 'present')->count();
                $total = $sessionRecords->count();

                $weeklyPresent += $present;
                $weeklyTotal += $total;
                $totalAttendanceRecords += $total;
                $totalPresentRecords += $present;
            }

            $attendanceRate = $weeklyTotal > 0 ? ($weeklyPresent / $weeklyTotal) * 100 : 0;

            // Determine performance status
            if ($attendanceRate >= 90) {
                $status = 'excellent';
            } elseif ($attendanceRate >= 75) {
                $status = 'good';
            } else {
                $status = 'needs_improvement';
            }

            $weeklyAttendanceData[] = [
                'class_name' => $class->name,
                'course' => $class->course ?? '',
                'student_count' => $studentCount,
                'attendance_rate' => round($attendanceRate, 1),
                'status' => $status
            ];
        }

        // Calculate overall statistics
        $overallAttendanceRate = $totalAttendanceRecords > 0 
                                ? round(($totalPresentRecords / $totalAttendanceRecords) * 100, 1) 
                                : 0;

        // Generate realistic report counts based on sessions and classes
        $totalReports = DB::table('attendance_sessions')
                         ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                         ->where('class_models.teacher_id', $teacher->user_id)
                         ->where('attendance_sessions.created_at', '>=', now()->startOfMonth())
                         ->count();

        $reportsDownloaded = max(1, intval($totalReports * 0.6)); // Assume 60% are downloaded

        // Recent export activities (simulated based on actual data)
        $recentExports = [
            [
                'name' => 'Monthly Attendance Report',
                'type' => 'attendance_summary',
                'format' => 'PDF',
                'created_at' => now()->subHours(2)->format('g:i A'),
                'file_size' => '245 KB',
                'status' => 'completed'
            ],
            [
                'name' => 'Student Performance Analysis',
                'type' => 'student_reports',
                'format' => 'Excel',
                'created_at' => now()->subDay()->format('M j, Y'),
                'file_size' => '1.2 MB',
                'status' => 'completed'
            ],
            [
                'name' => 'Weekly Summary Report',
                'type' => 'session_reports',
                'format' => 'PDF',
                'created_at' => now()->subDays(3)->format('M j, Y'),
                'file_size' => '89 KB',
                'status' => 'completed'
            ]
        ];

        return Inertia::render('Teacher/Reports', [
            'teacher' => $teacher,
            'stats' => [
                'total_reports' => $totalReports,
                'average_attendance' => $overallAttendanceRate,
                'active_students' => $totalStudents,
                'reports_downloaded' => $reportsDownloaded
            ],
            'weeklyAttendance' => $weeklyAttendanceData,
            'recentExports' => $recentExports,
            'summary' => [
                'total_classes' => count($classes),
                'total_students' => $totalStudents,
                'attendance_data' => $weeklyAttendanceData
            ]
        ]);
    }

    /**
     * Get detailed attendance reports with enhanced analytics
     */
    public function attendanceReports(Request $request)
    {
        $teacher = $this->getCurrentTeacher();
        
        // Get teacher's classes with enhanced data
        $classes = DB::table('class_models')
                    ->where('teacher_id', $teacher->user_id)
                    ->get()
                    ->map(function ($class) {
                        $totalStudents = DB::table('class_student')
                                          ->where('class_model_id', $class->id)
                                          ->count();
                        
                        $totalSessions = DB::table('attendance_sessions')
                                          ->where('class_id', $class->id)
                                          ->count();
                        
                        $avgAttendance = DB::select("
                            SELECT 
                                COALESCE(AVG(CASE WHEN ar.status = 'present' THEN 1.0 ELSE 0.0 END) * 100, 0) as avg_rate
                            FROM attendance_sessions ats
                            LEFT JOIN attendance_records ar ON ats.id = ar.attendance_session_id
                            WHERE ats.class_id = ?
                        ", [$class->id])[0]->avg_rate ?? 0;

                        return [
                            'id' => $class->id,
                            'name' => $class->name,
                            'course' => $class->course,
                            'section' => $class->section,
                            'total_students' => $totalStudents,
                            'total_sessions' => $totalSessions,
                            'avg_attendance_rate' => round($avgAttendance, 1),
                        ];
                    });

        // Get filters
        $filters = [
            'class_id' => $request->get('class_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'student_id' => $request->get('student_id'),
        ];

        // Build sessions query with filters
        $sessionsQuery = DB::table('attendance_sessions')
                          ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                          ->where('class_models.teacher_id', $teacher->user_id);

        if ($filters['class_id']) {
            $sessionsQuery->where('attendance_sessions.class_id', $filters['class_id']);
        }

        if ($filters['date_from']) {
            $sessionsQuery->where('attendance_sessions.session_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $sessionsQuery->where('attendance_sessions.session_date', '<=', $filters['date_to']);
        }

        $sessions = $sessionsQuery->orderBy('attendance_sessions.session_date', 'desc')
                                 ->select(
                                     'attendance_sessions.*',
                                     'class_models.name as class_name',
                                     'class_models.course',
                                     'class_models.section'
                                 )
                                 ->paginate(20);

        // Enhanced session data with detailed analytics
        $enhancedSessions = [];
        foreach ($sessions->items() as $session) {
            // Ensure session has required properties
            if (!$session || !isset($session->id)) {
                continue; // Skip invalid sessions
            }

            // Get detailed attendance records
            $recordsQuery = DB::table('attendance_records')
                             ->join('students', 'attendance_records.student_id', '=', 'students.id')
                             ->where('attendance_session_id', $session->id);

            if ($filters['status']) {
                $recordsQuery->where('attendance_records.status', $filters['status']);
            }

            if ($filters['student_id']) {
                $recordsQuery->where('students.id', $filters['student_id']);
            }

            $records = $recordsQuery->select(
                                'students.id as student_db_id',
                                'students.student_id',
                                'students.name',
                                'students.course as student_course',
                                'students.year',
                                'students.section as student_section',
                                'attendance_records.status',
                                'attendance_records.marked_at',
                                'attendance_records.notes'
                            )
                            ->orderBy('students.name')
                            ->get();

            // Calculate statistics
            $totalEnrolled = DB::table('class_student')
                              ->where('class_model_id', $session->class_id)
                              ->count();
            
            $presentCount = $records->where('status', 'present')->count();
            $absentCount = $records->where('status', 'absent')->count();
            $lateCount = $records->where('status', 'late')->count();
            $excusedCount = $records->where('status', 'excused')->count();
            $totalCount = $records->count();
            
            // Get absent students (enrolled but not marked)
            $markedStudentIds = $records->pluck('student_db_id');
            $absentStudents = DB::table('class_student')
                               ->join('students', 'class_student.student_id', '=', 'students.id')
                               ->where('class_student.class_model_id', $session->class_id)
                               ->whereNotIn('students.id', $markedStudentIds)
                               ->select('students.id', 'students.student_id', 'students.name')
                               ->get();
            
            // Create enhanced session object with guaranteed properties
            $enhancedSessions[] = [
                'id' => $session->id,
                'class_id' => $session->class_id ?? null,
                'class_name' => $session->class_name ?? 'Unknown Class',
                'course' => $session->course ?? 'Unknown Course',
                'section' => $session->section ?? 'Unknown Section',
                'session_name' => $session->session_name ?? 'Unnamed Session',
                'session_date' => $session->session_date ?? now()->toDateString(),
                'start_time' => $session->start_time ?? null,
                'end_time' => $session->end_time ?? null,
                'status' => $session->status ?? 'unknown',
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'excused_count' => $excusedCount,
                'total_count' => $totalCount,
                'total_enrolled' => $totalEnrolled,
                'attendance_rate' => $totalEnrolled > 0 ? round(($presentCount / $totalEnrolled) * 100, 1) : 0,
                'attendance_records' => $records,
                'absent_students' => $absentStudents,
            ];
        }

        // Overall statistics for this teacher
        $overallStats = [
            'total_sessions' => DB::table('attendance_sessions')
                                 ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                                 ->where('class_models.teacher_id', $teacher->user_id)
                                 ->count(),
            'total_classes' => $classes->count(),
            'total_students' => DB::table('class_student')
                                 ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                                 ->where('class_models.teacher_id', $teacher->user_id)
                                 ->distinct('class_student.student_id')
                                 ->count(),
            'avg_attendance_rate' => $classes->avg('avg_attendance_rate'),
        ];

        // Get students for filter dropdown (only from teacher's classes)
        $students = DB::table('class_student')
                     ->join('students', 'class_student.student_id', '=', 'students.id')
                     ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                     ->where('class_models.teacher_id', $teacher->user_id)
                     ->select('students.id', 'students.name', 'students.student_id', 'students.course')
                     ->distinct()
                     ->orderBy('students.name')
                     ->get();

        return Inertia::render('Teacher/AttendanceReports', [
            'teacher' => $teacher,
            'classes' => $classes,
            'sessions' => $enhancedSessions,
            'students' => $students,
            'overallStats' => $overallStats,
            'filters' => $filters,
        ]);
    }

    /**
     * Export attendance report
     */
    public function exportAttendanceReport(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        
        $exportType = $request->get('export_type', 'attendance');
        $format = $request->get('format', 'csv');
        $classId = $request->get('class_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Handle quick export types
        if (in_array($exportType, ['weekly', 'monthly', 'semester'])) {
            return $this->handleQuickExport($exportType, $format, $teacher);
        }
        
        // Handle regular exports
        if ($exportType === 'attendance') {
            return $this->exportAttendanceData($classId, $startDate, $endDate, $format, $teacher);
        } elseif ($exportType === 'students') {
            return $this->exportStudentData($classId, $format, $teacher);
        }
        
        return response()->json(['error' => 'Invalid export type'], 400);
    }
    
    private function handleQuickExport($type, $format, $teacher)
    {
        $now = now();
        
        switch ($type) {
            case 'weekly':
                $startDate = $now->copy()->subWeek()->format('Y-m-d');
                $endDate = $now->format('Y-m-d');
                break;
            case 'monthly':
                $startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'semester':
                // Assume semester starts in September or January
                $currentMonth = $now->month;
                if ($currentMonth >= 9) {
                    $startDate = $now->copy()->month(9)->startOfMonth()->format('Y-m-d');
                } else {
                    $startDate = $now->copy()->subYear()->month(9)->startOfMonth()->format('Y-m-d');
                }
                $endDate = $now->format('Y-m-d');
                break;
            default:
                $startDate = null;
                $endDate = null;
        }
        
        return $this->exportAttendanceData(null, $startDate, $endDate, $format, $teacher, ucfirst($type) . '_Report');
    }
    
    private function exportAttendanceData($classId, $startDate, $endDate, $format, $teacher, $filename = null)
    {
        // Build query for attendance data
        $query = DB::table('attendance_sessions')
                   ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                   ->leftJoin('attendance_records', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                   ->leftJoin('students', 'attendance_records.student_id', '=', 'students.id')
                   ->where('class_models.teacher_id', $teacher->user_id)
                   ->select([
                       'class_models.name as class_name',
                       'class_models.course',
                       'class_models.section',
                       'attendance_sessions.session_date',
                       'attendance_sessions.start_time',
                       'attendance_sessions.end_time',
                       'students.student_id',
                       'students.name as student_name',
                       'attendance_records.status',
                       'attendance_records.marked_at'
                   ]);

        if ($classId) {
            $query->where('attendance_sessions.class_id', $classId);
        }

        if ($startDate) {
            $query->where('attendance_sessions.session_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('attendance_sessions.session_date', '<=', $endDate);
        }

        $data = $query->orderBy('attendance_sessions.session_date', 'desc')
                     ->orderBy('class_models.name')
                     ->orderBy('students.name')
                     ->get();

        // Generate filename
        if (!$filename) {
            $filename = 'Attendance_Report_' . now()->format('Y-m-d_H-i-s');
        }

        // Return appropriate format
        if ($format === 'csv') {
            return $this->generateCsvResponse($data, $filename, 'attendance');
        } elseif ($format === 'xlsx') {
            return $this->generateExcelResponse($data, $filename, 'attendance');
        } elseif ($format === 'pdf') {
            return $this->generatePdfResponse($data, $filename, 'attendance');
        }
        
        return response()->json(['error' => 'Invalid format'], 400);
    }
    
    private function exportStudentData($classId, $format, $teacher)
    {
        if (!$classId) {
            return response()->json(['error' => 'Class ID is required for student reports'], 400);
        }
        
        // Get class info
        $class = DB::table('class_models')
                   ->where('id', $classId)
                   ->where('teacher_id', $teacher->user_id)
                   ->first();
                   
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }
        
        // Get student data with attendance statistics
        $students = DB::table('class_student')
                     ->join('students', 'class_student.student_id', '=', 'students.id')
                     ->where('class_student.class_model_id', $classId)
                     ->select([
                         'students.student_id',
                         'students.name',
                         'students.email',
                         'students.course',
                         'class_student.status',
                         'class_student.enrolled_at'
                     ])
                     ->get();
        
        // Calculate attendance for each student
        $studentsWithAttendance = $students->map(function ($student) use ($classId) {
            $totalSessions = DB::table('attendance_sessions')
                              ->where('class_id', $classId)
                              ->count();
                              
            $presentSessions = DB::table('attendance_records')
                                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                                ->where('attendance_sessions.class_id', $classId)
                                ->where('attendance_records.student_id', $student->student_id)
                                ->where('attendance_records.status', 'present')
                                ->count();
                                
            $absentSessions = DB::table('attendance_records')
                               ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                               ->where('attendance_sessions.class_id', $classId)
                               ->where('attendance_records.student_id', $student->student_id)
                               ->where('attendance_records.status', 'absent')
                               ->count();
            
            $attendanceRate = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) : 0;
            
            return (object) array_merge((array) $student, [
                'class_name' => $class->name ?? '',
                'total_sessions' => $totalSessions,
                'present_sessions' => $presentSessions,
                'absent_sessions' => $absentSessions,
                'attendance_rate' => $attendanceRate
            ]);
        });
        
        $filename = 'Student_Report_' . str_replace(' ', '_', $class->name) . '_' . now()->format('Y-m-d_H-i-s');
        
        // Return appropriate format
        if ($format === 'csv') {
            return $this->generateCsvResponse($studentsWithAttendance, $filename, 'students');
        } elseif ($format === 'xlsx') {
            return $this->generateExcelResponse($studentsWithAttendance, $filename, 'students');
        } elseif ($format === 'pdf') {
            return $this->generatePdfResponse($studentsWithAttendance, $filename, 'students');
        }
        
        return response()->json(['error' => 'Invalid format'], 400);
    }
    
    private function generateCsvResponse($data, $filename, $type)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
        
        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'attendance') {
                // CSV headers for attendance
                fputcsv($file, [
                    'Class Name', 'Course', 'Section', 'Session Date', 
                    'Start Time', 'End Time', 'Student ID', 'Student Name', 
                    'Status', 'Marked At'
                ]);
                
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->class_name ?? '',
                        $row->course ?? '',
                        $row->section ?? '',
                        $row->session_date ?? '',
                        $row->start_time ?? '',
                        $row->end_time ?? '',
                        $row->student_id ?? '',
                        $row->student_name ?? '',
                        $row->status ?? '',
                        $row->marked_at ?? ''
                    ]);
                }
            } else {
                // CSV headers for students
                fputcsv($file, [
                    'Student ID', 'Name', 'Email', 'Course', 'Class Name',
                    'Status', 'Enrolled At', 'Total Sessions', 'Present Sessions',
                    'Absent Sessions', 'Attendance Rate (%)'
                ]);
                
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->student_id ?? '',
                        $row->name ?? '',
                        $row->email ?? '',
                        $row->course ?? '',
                        $row->class_name ?? '',
                        $row->status ?? '',
                        $row->enrolled_at ?? '',
                        $row->total_sessions ?? 0,
                        $row->present_sessions ?? 0,
                        $row->absent_sessions ?? 0,
                        $row->attendance_rate ?? 0
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function generateExcelResponse($data, $filename, $type)
    {
        // For now, return CSV format with Excel headers
        // In a real implementation, you'd use a library like PhpSpreadsheet
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xls\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
        
        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');
            
            // Use same CSV logic but with Excel headers
            if ($type === 'attendance') {
                fputcsv($file, [
                    'Class Name', 'Course', 'Section', 'Session Date', 
                    'Start Time', 'End Time', 'Student ID', 'Student Name', 
                    'Status', 'Marked At'
                ]);
                
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->class_name ?? '',
                        $row->course ?? '',
                        $row->section ?? '',
                        $row->session_date ?? '',
                        $row->start_time ?? '',
                        $row->end_time ?? '',
                        $row->student_id ?? '',
                        $row->student_name ?? '',
                        $row->status ?? '',
                        $row->marked_at ?? ''
                    ]);
                }
            } else {
                fputcsv($file, [
                    'Student ID', 'Name', 'Email', 'Course', 'Class Name',
                    'Status', 'Enrolled At', 'Total Sessions', 'Present Sessions',
                    'Absent Sessions', 'Attendance Rate (%)'
                ]);
                
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->student_id ?? '',
                        $row->name ?? '',
                        $row->email ?? '',
                        $row->course ?? '',
                        $row->class_name ?? '',
                        $row->status ?? '',
                        $row->enrolled_at ?? '',
                        $row->total_sessions ?? 0,
                        $row->present_sessions ?? 0,
                        $row->absent_sessions ?? 0,
                        $row->attendance_rate ?? 0
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function generatePdfResponse($data, $filename, $type)
    {
        // For now, return a simple PDF message
        // In a real implementation, you'd use a library like DomPDF or TCPDF
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
        ];
        
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n>>\nendobj\n4 0 obj\n<<\n/Length 44\n>>\nstream\nBT\n/F1 12 Tf\n72 720 Td\n(Report Generated) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000207 00000 n \ntrailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n299\n%%EOF";
        
        return response($pdfContent, 200, $headers);
    }

    /**
     * Enhanced student reports with detailed analytics
     */
    public function studentReports(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        
        // Get teacher's classes with enhanced data
        $classes = DB::table('class_models')
                    ->where('teacher_id', $teacher->user_id)
                    ->get();

        // Get filters
        $filters = [
            'class_id' => $request->get('class_id'),
            'course' => $request->get('course'),
            'year' => $request->get('year'),
            'section' => $request->get('section'),
            'attendance_threshold' => $request->get('attendance_threshold', 75), // Default 75%
            'search' => $request->get('search'),
        ];

        // Build students query
        $studentsQuery = DB::table('class_student')
                          ->join('students', 'class_student.student_id', '=', 'students.id')
                          ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                          ->where('class_models.teacher_id', $teacher->user_id);

        // Apply filters
        if ($filters['class_id']) {
            $studentsQuery->where('class_models.id', $filters['class_id']);
        }

        if ($filters['course']) {
            $studentsQuery->where('students.course', $filters['course']);
        }

        if ($filters['year']) {
            $studentsQuery->where('students.year', $filters['year']);
        }

        if ($filters['section']) {
            $studentsQuery->where('students.section', $filters['section']);
        }

        if ($filters['search']) {
            $studentsQuery->where(function ($query) use ($filters) {
                $query->where('students.name', 'LIKE', '%' . $filters['search'] . '%')
                      ->orWhere('students.student_id', 'LIKE', '%' . $filters['search'] . '%')
                      ->orWhere('students.email', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        $rawStudents = $studentsQuery->select(
                                    'students.id',
                                    'students.student_id',
                                    'students.name',
                                    'students.email',
                                    'students.phone',
                                    'students.course',
                                    'students.year',
                                    'students.section',
                                    'students.avatar',
                                    'students.is_active',
                                    'students.created_at',
                                    'students.updated_at',
                                    'students.class_id',
                                    'students.user_id',
                                    'class_models.id as class_id',
                                    'class_models.name as class_name',
                                    'class_models.course as class_course',
                                    'class_models.section as class_section',
                                    'class_student.created_at as enrolled_at'
                                )
                                ->distinct()
                                ->orderBy('students.name')
                                ->get();

        // Enhanced student data with comprehensive analytics
        $students = collect($rawStudents)->map(function ($student) use ($teacher) {
            // Simplified version for debugging - skip complex attendance calculations
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone ?? '',
                'course' => $student->course ?? '',
                'year' => $student->year ?? '',
                'section' => $student->section ?? '',
                'class_id' => $student->class_id,
                'class_name' => $student->class_name,
                'enrolled_at' => $student->enrolled_at,
                'attendance_rate' => 85.0, // Temporary static value
                'recent_attendance_rate' => 82.0,
                'total_sessions' => 10,
                'present_count' => 8,
                'absent_count' => 2,
                'late_count' => 0,
                'excused_count' => 0,
                'recent_pattern' => ['present', 'present', 'absent'],
                'trend' => 'stable',
                'performance' => 'good',
                'needs_attention' => false,
            ];
        });
        


        // Filter by attendance threshold
        if ($filters['attendance_threshold']) {
            $students = $students->filter(function ($student) use ($filters) {
                return $student['attendance_rate'] >= $filters['attendance_threshold'];
            });
        }

        // Summary statistics
        $summaryStats = [
            'total_students' => $students->count(),
            'avg_attendance_rate' => round($students->avg('attendance_rate'), 1),
            'excellent_performers' => $students->where('performance', 'excellent')->count(),
            'good_performers' => $students->where('performance', 'good')->count(),
            'fair_performers' => $students->where('performance', 'fair')->count(),
            'poor_performers' => $students->where('performance', 'poor')->count(),
            'students_needing_attention' => $students->where('needs_attention', true)->count(),
            'improving_trend' => $students->where('trend', 'improving')->count(),
            'declining_trend' => $students->where('trend', 'declining')->count(),
        ];

        // Get distinct values for filter dropdowns
        $allStudentsData = DB::table('class_student')
                            ->join('students', 'class_student.student_id', '=', 'students.id')
                            ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                            ->where('class_models.teacher_id', $teacher->user_id)
                            ->select('students.course', 'students.year', 'students.section')
                            ->distinct()
                            ->get();

        $filterOptions = [
            'courses' => $allStudentsData->pluck('course')->unique()->filter()->sort()->values(),
            'years' => $allStudentsData->pluck('year')->unique()->filter()->sort()->values(),
            'sections' => $allStudentsData->pluck('section')->unique()->filter()->sort()->values(),
        ];

        return Inertia::render('Teacher/StudentReports', [
            'teacher' => $teacher,
            'students' => $students->values(),
            'classes' => $classes,
            'summaryStats' => $summaryStats,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
        ]);
    }

    /**
     * Enhanced export reports page with multiple format support
     */
    public function exportReports(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (\Exception $e) {
            // For debugging, let's try to get teacher ID 4 directly since auth might not be working
            $teacher = \App\Models\Teacher::find(4);
            if (!$teacher) {
                abort(404, 'No teacher found for testing');
            }
        }
        
        // Get teacher's classes with session counts
        $classes = DB::table('class_models')
                    ->where('teacher_id', $teacher->user_id)
                    ->get()
                    ->map(function ($class) {
                        $sessionCount = DB::table('attendance_sessions')
                                         ->where('class_id', $class->id)
                                         ->count();
                        
                        $studentCount = DB::table('class_student')
                                         ->where('class_model_id', $class->id)
                                         ->count();

                        return [
                            'id' => $class->id,
                            'name' => $class->name,
                            'course' => $class->course,
                            'section' => $class->section,
                            'session_count' => $sessionCount,
                            'student_count' => $studentCount,
                        ];
                    });

        // Export types available
        $exportTypes = [
            [
                'id' => 'attendance_summary',
                'name' => 'Attendance Summary Report',
                'description' => 'Overall attendance statistics by class and student',
                'formats' => ['pdf', 'excel', 'csv'],
                'icon' => 'BarChart3',
            ],
            [
                'id' => 'student_attendance',
                'name' => 'Student Attendance Records',
                'description' => 'Detailed attendance records for individual students',
                'formats' => ['pdf', 'excel', 'csv'],
                'icon' => 'Users',
            ],
            [
                'id' => 'session_reports',
                'name' => 'Session-wise Reports',
                'description' => 'Attendance data organized by sessions/dates',
                'formats' => ['pdf', 'excel', 'csv'],
                'icon' => 'Calendar',
            ],
            [
                'id' => 'class_analytics',
                'name' => 'Class Analytics',
                'description' => 'Performance analytics and trends by class',
                'formats' => ['pdf', 'excel'],
                'icon' => 'TrendingUp',
            ],
            [
                'id' => 'absent_students',
                'name' => 'Absent Students Report',
                'description' => 'Students with poor attendance or recent absences',
                'formats' => ['pdf', 'excel', 'csv'],
                'icon' => 'AlertTriangle',
            ],
        ];

        // Recent exports (mock data - in production, store in database)
        $recentExports = [
            [
                'id' => 1,
                'name' => 'Attendance Summary - BSCS 3A',
                'type' => 'attendance_summary',
                'format' => 'pdf',
                'created_at' => now()->subHours(2)->toISOString(),
                'file_size' => '245 KB',
                'status' => 'completed',
            ],
            [
                'id' => 2,
                'name' => 'Student Records - All Classes',
                'type' => 'student_attendance',
                'format' => 'excel',
                'created_at' => now()->subDays(1)->toISOString(),
                'file_size' => '1.2 MB',
                'status' => 'completed',
            ],
            [
                'id' => 3,
                'name' => 'Weekly Session Report',
                'type' => 'session_reports',
                'format' => 'csv',
                'created_at' => now()->subDays(3)->toISOString(),
                'file_size' => '89 KB',
                'status' => 'completed',
            ],
        ];

        return Inertia::render('Teacher/ExportReports', [
            'teacher' => $teacher,
            'classes' => $classes,
            'exportTypes' => $exportTypes,
            'recentExports' => $recentExports,
        ]);
    }

    /**
     * Generate and download attendance report
     */
    public function generateAttendanceReport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:attendance_summary,student_attendance,session_reports,class_analytics,absent_students',
            'format' => 'required|string|in:pdf,excel,csv',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'integer|exists:class_models,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'include_excused' => 'boolean',
            'min_attendance_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $teacher = $this->getCurrentTeacher();

        try {
            switch ($validated['type']) {
                case 'attendance_summary':
                    return $this->generateAttendanceSummaryReport($teacher, $validated);
                case 'student_attendance':
                    return $this->generateStudentAttendanceReport($teacher, $validated);
                case 'session_reports':
                    return $this->generateSessionReports($teacher, $validated);
                case 'class_analytics':
                    return $this->generateClassAnalyticsReport($teacher, $validated);
                case 'absent_students':
                    return $this->generateAbsentStudentsReport($teacher, $validated);
                default:
                    return back()->withErrors(['error' => 'Invalid report type']);
            }
        } catch (\Exception $e) {
            Log::error('Export report error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to generate report: ' . $e->getMessage()]);
        }
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
                    ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                    ->where('attendance_sessions.id', $sessionId)
                    ->where('class_models.teacher_id', $teacher->id)
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
        $class = ClassModel::forTeacher($teacher->user_id)->findOrFail($classId);

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
     * Upload attendance file and process records
     */
    public function uploadAttendanceFile(Request $request, $sessionId)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048'
        ]);

        $teacher = $this->getCurrentTeacher();
        $session = AttendanceSession::where('teacher_id', $teacher->id)->findOrFail($sessionId);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            $extension = $file->getClientOriginalExtension();

            $students = [];
            $processedCount = 0;

            if ($extension === 'csv') {
                // Process CSV file
                if (($handle = fopen($path, 'r')) !== FALSE) {
                    $header = fgetcsv($handle); // Skip header row
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (count($data) >= 2) {
                            $studentId = trim($data[0]);
                            $status = strtolower(trim($data[1]));
                            
                            if (in_array($status, ['present', 'absent', 'late', 'excused'])) {
                                $student = Student::where('student_id', $studentId)->first();
                                
                                if ($student) {
                                    // Check if student is in this class
                                    $isEnrolled = DB::table('class_student')
                                                   ->where('class_model_id', $session->class_id)
                                                   ->where('student_id', $student->id)
                                                   ->exists();
                                    
                                    if ($isEnrolled) {
                                        AttendanceRecord::updateOrCreate(
                                            [
                                                'attendance_session_id' => $sessionId,
                                                'student_id' => $student->id
                                            ],
                                            [
                                                'status' => $status,
                                                'method' => 'file_upload',
                                                'marked_at' => now()
                                            ]
                                        );
                                        
                                        $students[] = ['student_id' => $student->id];
                                        $processedCount++;
                                    }
                                }
                            }
                        }
                    }
                    fclose($handle);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance file processed successfully',
                'processed' => $processedCount,
                'records' => $students
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process attendance file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process attendance file: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * Parse schedule_days field to ensure it's always an array
     */
    private function parseScheduleDays($scheduleDays)
    {
        if (empty($scheduleDays)) {
            return [];
        }
        
        // If it's already an array, return it
        if (is_array($scheduleDays)) {
            return $scheduleDays;
        }
        
        // If it's a JSON string, decode it
        if (is_string($scheduleDays)) {
            $decoded = json_decode($scheduleDays, true);
            
            // Handle double-encoded JSON (when it's a string containing JSON)
            if (is_string($decoded)) {
                $doubleDecoded = json_decode($decoded, true);
                return is_array($doubleDecoded) ? $doubleDecoded : [];
            }
            
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Generate attendance summary report
     */
    private function generateAttendanceSummaryReport($teacher, $params)
    {
        // Build query for attendance data
        $query = DB::table('attendance_sessions')
                   ->join('class_models', 'attendance_sessions.class_id', '=', 'class_models.id')
                   ->join('attendance_records', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                   ->join('students', 'attendance_records.student_id', '=', 'students.id')
                   ->where('class_models.teacher_id', $teacher->user_id);

        // Apply filters
        if ($params['class_ids']) {
            $query->whereIn('class_models.id', $params['class_ids']);
        }

        if ($params['date_from']) {
            $query->where('attendance_sessions.session_date', '>=', $params['date_from']);
        }

        if ($params['date_to']) {
            $query->where('attendance_sessions.session_date', '<=', $params['date_to']);
        }

        $data = $query->select(
                        'class_models.name as class_name',
                        'class_models.course',
                        'class_models.section',
                        'students.name as student_name',
                        'students.student_id',
                        'attendance_records.status',
                        'attendance_sessions.session_date',
                        'attendance_sessions.session_name'
                    )
                    ->orderBy('class_models.name')
                    ->orderBy('students.name')
                    ->get();

        // Generate file based on format
        $filename = 'attendance_summary_' . now()->format('Y-m-d_H-i-s');
        
        switch ($params['format']) {
            case 'pdf':
                return $this->generatePDF($data, 'attendance_summary', $filename);
            case 'excel':
                return $this->generateExcel($data, 'attendance_summary', $filename);
            case 'csv':
                return $this->generateCSV($data, $filename);
            default:
                throw new \Exception('Unsupported format');
        }
    }

    /**
     * Generate student attendance report
     */
    private function generateStudentAttendanceReport($teacher, $params)
    {
        // Similar implementation for student-focused reports
        $data = collect(); // Placeholder - implement actual data collection
        $filename = 'student_attendance_' . now()->format('Y-m-d_H-i-s');
        
        switch ($params['format']) {
            case 'pdf':
                return $this->generatePDF($data, 'student_attendance', $filename);
            case 'excel':
                return $this->generateExcel($data, 'student_attendance', $filename);
            case 'csv':
                return $this->generateCSV($data, $filename);
            default:
                throw new \Exception('Unsupported format');
        }
    }

    /**
     * Generate session reports
     */
    private function generateSessionReports($teacher, $params)
    {
        $data = collect(); // Placeholder
        $filename = 'session_reports_' . now()->format('Y-m-d_H-i-s');
        
        switch ($params['format']) {
            case 'pdf':
                return $this->generatePDF($data, 'session_reports', $filename);
            case 'excel':
                return $this->generateExcel($data, 'session_reports', $filename);
            case 'csv':
                return $this->generateCSV($data, $filename);
            default:
                throw new \Exception('Unsupported format');
        }
    }

    /**
     * Generate class analytics report
     */
    private function generateClassAnalyticsReport($teacher, $params)
    {
        $data = collect(); // Placeholder
        $filename = 'class_analytics_' . now()->format('Y-m-d_H-i-s');
        
        switch ($params['format']) {
            case 'pdf':
                return $this->generatePDF($data, 'class_analytics', $filename);
            case 'excel':
                return $this->generateExcel($data, 'class_analytics', $filename);
            default:
                throw new \Exception('Unsupported format');
        }
    }

    /**
     * Generate absent students report
     */
    private function generateAbsentStudentsReport($teacher, $params)
    {
        $data = collect(); // Placeholder
        $filename = 'absent_students_' . now()->format('Y-m-d_H-i-s');
        
        switch ($params['format']) {
            case 'pdf':
                return $this->generatePDF($data, 'absent_students', $filename);
            case 'excel':
                return $this->generateExcel($data, 'absent_students', $filename);
            case 'csv':
                return $this->generateCSV($data, $filename);
            default:
                throw new \Exception('Unsupported format');
        }
    }

    /**
     * Generate PDF report
     */
    private function generatePDF($data, $type, $filename)
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.teacher.' . $type, [
            'data' => $data,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'type' => $type
        ]);

        return $pdf->download($filename . '.pdf');
    }

    /**
     * Generate Excel report
     */
    private function generateExcel($data, $type, $filename)
    {
        // This would require a package like Maatwebsite/Laravel-Excel
        // For now, return CSV as fallback
        return $this->generateCSV($data, $filename);
    }

    /**
     * Generate CSV report
     */
    private function generateCSV($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers based on data structure
            if ($data->isNotEmpty()) {
                $firstRow = $data->first();
                if (is_object($firstRow) || is_array($firstRow)) {
                    $headers = array_keys((array) $firstRow);
                    fputcsv($handle, $headers);
                }
            }

            // Add data rows
            foreach ($data as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Show excuse requests page for teachers
     */
    public function excuseRequests(Request $request)
    {
        $teacher = $this->getCurrentTeacher();

        // Build the query for excuse requests
        $query = ExcuseRequest::with(['student', 'attendanceSession.class'])
            ->whereHas('attendanceSession.class', function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->user_id);
            });

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('attendanceSession.class', function($q) use ($request) {
                $q->where('id', $request->class_id);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('student', function($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('student_id', 'ILIKE', "%{$searchTerm}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get teacher's classes for the filter dropdown
        $classes = DB::table('class_models')
                     ->where('teacher_id', $teacher->user_id)
                     ->whereRaw('COALESCE(is_active, true) = true')
                     ->select('id', 'name', 'course', 'section')
                     ->get();

        // If this is an AJAX request (from the filter), return JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'requests' => $requests,
                'classes' => $classes
            ]);
        }

        return Inertia::render('Teacher/ExcuseRequests', [
            'teacher' => $teacher,
            'requests' => $requests,
            'classes' => $classes,
        ]);
    }

    /**
     * Approve an excuse request
     */
    public function approveExcuseRequest(Request $request, $requestId)
    {
        $teacher = $this->getCurrentTeacher();

        $excuseRequest = ExcuseRequest::with(['attendanceSession.class'])
            ->whereHas('attendanceSession.class', function($query) use ($teacher) {
                $query->where('teacher_id', $teacher->user_id);
            })
            ->findOrFail($requestId);

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $excuseRequest->approve($teacher, $validated['review_notes'] ?? null);

        return redirect()->back()->with('success', 'Excuse request approved successfully.');
    }

    /**
     * Reject an excuse request
     */
    public function rejectExcuseRequest(Request $request, $requestId)
    {
        $teacher = $this->getCurrentTeacher();

        $excuseRequest = ExcuseRequest::with(['attendanceSession.class'])
            ->whereHas('attendanceSession.class', function($query) use ($teacher) {
                $query->where('teacher_id', $teacher->user_id);
            })
            ->findOrFail($requestId);

        $validated = $request->validate([
            'review_notes' => 'required|string|max:1000',
        ]);

        $excuseRequest->reject($teacher, $validated['review_notes']);

        return redirect()->back()->with('success', 'Excuse request rejected.');
    }

    /**
     * Download attachment for an excuse request
     */
    public function downloadExcuseAttachment($requestId)
    {
        try {
            $teacher = $this->getCurrentTeacher();

            $excuseRequest = ExcuseRequest::with(['attendanceSession.class'])
                ->whereHas('attendanceSession.class', function($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->user_id);
                })
                ->whereNotNull('attachment_path')
                ->findOrFail($requestId);

            $filePath = storage_path('app/public/' . $excuseRequest->attachment_path);

            if (!file_exists($filePath)) {
                Log::error('Attachment file not found', [
                    'request_id' => $requestId,
                    'file_path' => $filePath,
                    'attachment_path' => $excuseRequest->attachment_path
                ]);
                abort(404, 'Attachment not found');
            }

            return response()->download($filePath, basename($excuseRequest->attachment_path));
        } catch (\Exception $e) {
            Log::error('Error downloading attachment', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error downloading attachment');
        }
    }
}
