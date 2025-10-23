<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display attendance management page based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        switch ($user->role ?? 'teacher') {
            case 'teacher':
                return $this->teacherView($request);
            case 'admin':
                return $this->adminView($request);
            case 'student':
                return $this->studentView($request);
            default:
                return $this->teacherView($request); // Default fallback
        }
    }

    /**
     * Teacher view - manage classes and record attendance
     */
    private function teacherView(Request $request)
    {
        $teacherId = Auth::id();
        
        // Get teacher's classes
        $classes = ClassModel::where('teacher_id', $teacherId)
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'studentCount' => $class->getStudentsCountBySection(),
                    'schedule' => $class->schedule_time ? $class->schedule_time : null,
                ];
            });

        // Get today's attendance statistics
        $today = Carbon::today();
        $attendanceStats = $this->getTeacherAttendanceStats($teacherId, $today);

        // Get today's sessions
        $todaySessions = $this->getTodaySessions($teacherId, $today);

        return Inertia::render('Attendance', [
            'userRole' => 'teacher',
            'classes' => $classes,
            'attendanceStats' => $attendanceStats,
            'todaySessions' => $todaySessions,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Admin view - view all attendance across the system
     */
    private function adminView(Request $request)
    {
        // Get all classes with teacher information
        $classes = ClassModel::with(['teacher', 'teacherRecord'])->get()
            ->map(function ($class) {
                $teacherName = 'No Teacher Assigned';
                if ($class->teacherRecord) {
                    $teacherName = $class->teacherRecord->full_name;
                } else if ($class->teacher) {
                    $teacherName = $class->teacher->name;
                }
                
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'studentCount' => $class->getStudentsCountBySection(),
                    'teacher' => $teacherName,
                    'schedule' => $class->schedule_time ? $class->schedule_time : null,
                ];
            });

        // Get system-wide attendance statistics
        $today = Carbon::today();
        $attendanceStats = $this->getSystemAttendanceStats($today);

        // Get all today's sessions
        $todaySessions = $this->getAllTodaySessions($today);

        return Inertia::render('Attendance', [
            'userRole' => 'admin',
            'classes' => $classes,
            'attendanceStats' => $attendanceStats,
            'todaySessions' => $todaySessions,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Student view - view their own attendance history
     */
    private function studentView(Request $request)
    {
        $user = Auth::user();
        
        // Get student record using the relationship
        $student = $user->student;
        if (!$student) {
            // Try to find student by email
            $student = Student::where('email', $user->email)->first();
        }
        
        if (!$student) {
            // For development/demo purposes, create a default student record
            $student = Student::create([
                'student_id' => 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'name' => $user->name,
                'email' => $user->email,
                'course' => 'BSCS',
                'section' => 'A',
                'year' => 1,
                'is_active' => true
            ]);
        }

        // Get student's attendance history from database
        $attendanceHistory = Attendance::where('student_id', $student->id)
            ->with(['class', 'excuse'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($attendance) {
                $excuseData = null;
                if ($attendance->excuse) {
                    $excuseData = [
                        'id' => $attendance->excuse->id,
                        'reason' => $attendance->excuse->reason,
                        'status' => $attendance->excuse->status,
                        'submitted_at' => $attendance->excuse->submitted_at,
                        'reviewed_at' => $attendance->excuse->reviewed_at,
                        'review_notes' => $attendance->excuse->review_notes,
                    ];
                }

                return [
                    'id' => $attendance->id,
                    'date' => $attendance->created_at->format('Y-m-d'),
                    'status' => $attendance->status,
                    'time_in' => $attendance->scanned_at ? $attendance->scanned_at->format('H:i') : null,
                    'time_out' => null,
                    'class_name' => $attendance->class ? $attendance->class->name : 'N/A',
                    'excuse' => $excuseData,
                ];
            });

        // Get attendance statistics for student from database
        $totalDays = Attendance::where('student_id', $student->id)->count();
        $presentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'present')->count();
        $absentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'absent')->count();
        $excusedDays = Attendance::where('student_id', $student->id)
            ->where('status', 'excused')->count();

        $attendanceStats = [
            'totalDays' => $totalDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'excusedDays' => $excusedDays,
            'attendanceRate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
        ];

        return Inertia::render('Attendance', [
            'userRole' => 'student',
            'student' => $student,
            'attendanceHistory' => $attendanceHistory,
            'attendanceStats' => $attendanceStats,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Start QR attendance session for a specific class
     */
    public function startQRSession(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $user = Auth::user();
        $class = ClassModel::findOrFail($validated['class_id']);

        // Check if user can manage this class
        if ($user->role === 'teacher' && $class->teacher_id !== $user->id) {
            return response()->json([
                'error' => 'You can only start sessions for your own classes'
            ], 403);
        }

        // Generate unique session code
        $sessionCode = 'QR-' . $class->id . '-' . time();
        
        // Store session in cache (expires in 2 hours)
        cache()->put("qr_session_{$sessionCode}", [
            'class_id' => $class->id,
            'teacher_id' => $user->id,
            'started_at' => now(),
            'active' => true
        ], now()->addHours(2));

        return response()->json([
            'success' => true,
            'session_code' => $sessionCode,
            'class_name' => $class->name,
            'qr_data' => json_encode([
                'session_code' => $sessionCode,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'timestamp' => time()
            ])
        ]);
    }

    /**
     * End QR attendance session
     */
    public function endQRSession(Request $request)
    {
        $validated = $request->validate([
            'session_code' => 'required|string',
        ]);

        $sessionData = cache()->get("qr_session_{$validated['session_code']}");
        
        if (!$sessionData) {
            return response()->json([
                'error' => 'Session not found or expired'
            ], 404);
        }

        // Mark session as inactive
        $sessionData['active'] = false;
        cache()->put("qr_session_{$validated['session_code']}", $sessionData, now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'QR session ended successfully'
        ]);
    }

    /**
     * Improved QR Code scan endpoint
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'session_code' => 'required|string',
            'student_id' => 'required|string', // From QR code or manual input
        ]);

        // Validate session
        $sessionData = cache()->get("qr_session_{$validated['session_code']}");
        
        if (!$sessionData || !$sessionData['active']) {
            return response()->json([
                'success' => false,
                'message' => 'QR session is not active or has expired'
            ], 400);
        }

        $classId = $sessionData['class_id'];
        $class = ClassModel::findOrFail($classId);

        // Find student
        $student = Student::where('student_id', $validated['student_id'])
                        ->orWhere('id', $validated['student_id'])
                        ->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in database'
            ], 404);
        }

        // Validate student belongs to this class (by course/section)
        if ($student->course !== $class->course || $student->section !== $class->section) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not enrolled in this class'
            ], 403);
        }

        // Check for duplicate attendance today
        $today = Carbon::today();
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('class_id', $classId)
            ->where('date', $today->format('Y-m-d'))
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this student today',
                'existing_status' => $existingAttendance->status,
                'time_recorded' => $existingAttendance->scanned_at
            ], 409);
        }

        // Create attendance record
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'teacher_id' => $sessionData['teacher_id'],
            'status' => 'present',
            'scanned_at' => now(),
            'date' => $today->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => [
                'student_name' => $student->name,
                'student_id' => $student->student_id,
                'class_name' => $class->name,
                'time_recorded' => $attendance->scanned_at->format('H:i:s'),
                'status' => $attendance->status
            ]
        ]);
    }

    /**
     * Get active QR sessions for teacher
     */
    public function getActiveQRSessions(Request $request)
    {
        $user = Auth::user();
        $sessions = [];

        // Get all cache keys that match QR session pattern
        $cacheKeys = cache()->getStore()->getRedis()->keys('*qr_session_*');
        
        foreach ($cacheKeys as $key) {
            $sessionData = cache()->get(str_replace(config('cache.prefix') . ':', '', $key));
            
            if ($sessionData && 
                $sessionData['active'] && 
                ($user->role === 'admin' || $sessionData['teacher_id'] === $user->id)) {
                
                $class = ClassModel::find($sessionData['class_id']);
                if ($class) {
                    $sessions[] = [
                        'session_code' => str_replace('qr_session_', '', str_replace(config('cache.prefix') . ':', '', $key)),
                        'class_name' => $class->name,
                        'class_id' => $class->id,
                        'started_at' => $sessionData['started_at'],
                        'teacher_id' => $sessionData['teacher_id']
                    ];
                }
            }
        }

        return response()->json([
            'active_sessions' => $sessions
        ]);
    }

    /**
     * Submit excuse for absence
     */
    public function submitExcuse(Request $request)
    {
        $validated = $request->validate([
            'attendance_id' => 'required|exists:attendance,id',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // 2MB max
        ]);

        try {
            // Check if attendance record exists and belongs to current user (if student)
            $attendance = Attendance::with('student')->findOrFail($validated['attendance_id']);
            
            // If user is a student, verify they own this attendance record
            $user = Auth::user();
            if ($user->role === 'student') {
                // Get student record by email or user relationship
                $student = $user->student ?? Student::where('email', $user->email)->first();
                if (!$student || $attendance->student_id !== $student->id) {
                    return response()->json([
                        'error' => 'You can only submit excuses for your own attendance records'
                    ], 403);
                }
            }

            // Check if excuse already exists for this attendance
            $existingExcuse = \App\Models\Excuse::where('attendance_id', $attendance->id)->first();
            if ($existingExcuse) {
                return response()->json([
                    'error' => 'An excuse has already been submitted for this attendance record'
                ], 409);
            }

            // Handle file upload if provided
            $documentPath = null;
            if ($request->hasFile('supporting_document')) {
                $file = $request->file('supporting_document');
                $filename = time() . '_' . $file->getClientOriginalName();
                $documentPath = $file->storeAs('excuses', $filename, 'public');
            }

            // Create excuse record
            $excuse = \App\Models\Excuse::create([
                'attendance_id' => $attendance->id,
                'reason' => $validated['reason'],
                'supporting_document' => $documentPath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Excuse submitted successfully and is pending review',
                'data' => $excuse
            ]);

        } catch (\Exception $e) {
            Log::error('Excuse submission error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to submit excuse. Please try again.'
            ], 500);
        }
    }

    /**
     * Manage excuses (Teacher/Admin view)
     */
    public function manageExcuses(Request $request)
    {
        $user = Auth::user();
        
        // Get excuses based on user role
        if ($user->role === 'admin') {
            // Admins can see all excuses
            $excuses = \App\Models\Excuse::with([
                'attendance.student',
                'attendance.class',
                'reviewer'
            ])->orderBy('submitted_at', 'desc')->get();
        } else {
            // Teachers can only see excuses for their classes
            $teacherClasses = ClassModel::where('teacher_id', $user->id)->pluck('id');
            $excuses = \App\Models\Excuse::with([
                'attendance.student',
                'attendance.class',
                'reviewer'
            ])
            ->whereHas('attendance', function($query) use ($teacherClasses) {
                $query->whereIn('class_id', $teacherClasses);
            })
            ->orderBy('submitted_at', 'desc')
            ->get();
        }

        $excusesData = $excuses->map(function ($excuse) {
            return [
                'id' => $excuse->id,
                'student_name' => $excuse->attendance->student->name,
                'student_id' => $excuse->attendance->student->student_id,
                'class_name' => $excuse->attendance->class->name,
                'attendance_date' => $excuse->attendance->created_at->format('Y-m-d'),
                'reason' => $excuse->reason,
                'supporting_document' => $excuse->supporting_document,
                'status' => $excuse->status,
                'submitted_at' => $excuse->submitted_at,
                'reviewed_at' => $excuse->reviewed_at,
                'reviewed_by' => $excuse->reviewer ? $excuse->reviewer->name : null,
                'review_notes' => $excuse->review_notes,
            ];
        });

        return response()->json([
            'excuses' => $excusesData,
            'user_role' => $user->role
        ]);
    }

    /**
     * Approve an excuse
     */
    public function approveExcuse(\App\Models\Excuse $excuse, Request $request)
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500'
        ]);

        try {
            $user = Auth::user();
            
            // Check authorization
            if ($user->role === 'teacher') {
                $teacherClasses = ClassModel::where('teacher_id', $user->id)->pluck('id');
                if (!$teacherClasses->contains($excuse->attendance->class_id)) {
                    return response()->json([
                        'error' => 'You can only review excuses for your own classes'
                    ], 403);
                }
            }

            // Approve the excuse
            $excuse->approve($user, $validated['review_notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Excuse approved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Excuse approval error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to approve excuse. Please try again.'
            ], 500);
        }
    }

    /**
     * Get system-wide attendance overview (Admin only)
     */
    public function getSystemOverview(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // Get all students across all classes with their attendance for the specified date
        $studentsWithAttendance = Student::with(['attendances' => function($query) use ($date) {
            $query->whereDate('created_at', $date)
                  ->with(['class', 'excuse']);
        }, 'classes'])
        ->get()
        ->map(function ($student) use ($date) {
            $attendance = $student->attendances->first();
            $studentClasses = $student->classes->pluck('name')->join(', ');
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'studentId' => $student->student_id,
                'email' => $student->email,
                'classes' => $studentClasses,
                'status' => $attendance ? $attendance->status : 'no-record',
                'time_in' => $attendance && $attendance->scanned_at ? $attendance->scanned_at->format('H:i') : null,
                'class_name' => $attendance && $attendance->class ? $attendance->class->name : null,
                'has_excuse' => $attendance && $attendance->excuse ? true : false,
                'excuse_status' => $attendance && $attendance->excuse ? $attendance->excuse->status : null,
            ];
        });

        // Get summary statistics
        $totalStudents = $studentsWithAttendance->count();
        $presentCount = $studentsWithAttendance->where('status', 'present')->count();
        $absentCount = $studentsWithAttendance->where('status', 'absent')->count();
        $excusedCount = $studentsWithAttendance->where('status', 'excused')->count();
        $noRecordCount = $studentsWithAttendance->where('status', 'no-record')->count();

        return response()->json([
            'students' => $studentsWithAttendance,
            'summary' => [
                'total' => $totalStudents,
                'present' => $presentCount,
                'absent' => $absentCount,
                'excused' => $excusedCount,
                'no_record' => $noRecordCount,
                'attendance_rate' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0
            ],
            'date' => $date
        ]);
    }

    /**
     * Reject an excuse
     */
    public function rejectExcuse(\App\Models\Excuse $excuse, Request $request)
    {
        $validated = $request->validate([
            'review_notes' => 'required|string|max:500'
        ]);

        try {
            $user = Auth::user();
            
            // Check authorization
            if ($user->role === 'teacher') {
                $teacherClasses = ClassModel::where('teacher_id', $user->id)->pluck('id');
                if (!$teacherClasses->contains($excuse->attendance->class_id)) {
                    return response()->json([
                        'error' => 'You can only review excuses for your own classes'
                    ], 403);
                }
            }

            // Reject the excuse
            $excuse->reject($user, $validated['review_notes']);

            return response()->json([
                'success' => true,
                'message' => 'Excuse rejected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Excuse rejection error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to reject excuse. Please try again.'
            ], 500);
        }
    }

    /**
     * Get teacher's attendance statistics
     */
    private function getTeacherAttendanceStats($teacherId, $date)
    {
        // Get teacher's classes
        $classes = ClassModel::where('teacher_id', $teacherId)->get();
        $classIds = $classes->pluck('id');
        
        // Calculate total students across all teacher's classes
        $totalStudents = $classes->sum(function($class) {
            return $class->getStudentsCountBySection();
        });
        
        // Get today's attendance for teacher's classes
        $todayAttendance = Attendance::whereIn('class_id', $classIds)
            ->whereBetween('created_at', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay()
            ])
            ->get();

        $presentToday = $todayAttendance->where('status', 'present')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();
        $excusedToday = $todayAttendance->where('status', 'excused')->count();
        $activeClasses = $classes->count();

        return [
            'totalStudents' => $totalStudents,
            'presentToday' => $presentToday,
            'absentToday' => $absentToday,
            'excusedToday' => $excusedToday,
            'activeClasses' => $activeClasses,
        ];
    }

    /**
     * Get system-wide attendance statistics
     */
    private function getSystemAttendanceStats($date)
    {
        // Get all classes
        $classes = ClassModel::get();
        
        // Calculate total students across all classes
        $totalStudents = $classes->sum(function($class) {
            return $class->getStudentsCountBySection();
        });
        
        // Get today's attendance for all classes
        $todayAttendance = Attendance::whereBetween('created_at', [
            Carbon::parse($date)->startOfDay(),
            Carbon::parse($date)->endOfDay()
        ])->get();

        $presentToday = $todayAttendance->where('status', 'present')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();
        $excusedToday = $todayAttendance->where('status', 'excused')->count();
        $activeClasses = $classes->count();

        return [
            'totalStudents' => $totalStudents,
            'presentToday' => $presentToday,
            'absentToday' => $absentToday,
            'excusedToday' => $excusedToday,
            'activeClasses' => $activeClasses,
        ];
    }

    /**
     * Get today's sessions for teacher
     */
    private function getTodaySessions($teacherId, $date)
    {
        return ClassModel::where('teacher_id', $teacherId)
            ->get()
            ->map(function ($class) use ($date) {
                // Count present students for this class today
                $presentCount = Attendance::where('class_id', $class->id)
                    ->whereBetween('created_at', [
                        Carbon::parse($date)->startOfDay(),
                        Carbon::parse($date)->endOfDay()
                    ])
                    ->where('status', 'present')
                    ->count();

                // Check if attendance has been taken for this class today
                $hasAttendance = Attendance::where('class_id', $class->id)
                    ->whereBetween('created_at', [
                        Carbon::parse($date)->startOfDay(),
                        Carbon::parse($date)->endOfDay()
                    ])
                    ->exists();

                return [
                    'id' => $class->id,
                    'className' => $class->name,
                    'time' => $class->schedule_time ? $class->schedule_time : null,
                    'status' => $hasAttendance ? 'completed' : 'scheduled',
                    'presentCount' => $presentCount,
                    'totalStudents' => $class->getStudentsCountBySection(),
                    'classId' => $class->id,
                ];
            });
    }

    /**
     * Get all today's sessions (Admin view)
     */
    private function getAllTodaySessions($date)
    {
        return ClassModel::with(['teacher', 'teacherRecord'])
            ->get()
            ->map(function ($class) use ($date) {
                // Count present students for this class today
                $presentCount = Attendance::where('class_id', $class->id)
                    ->whereBetween('created_at', [
                        Carbon::parse($date)->startOfDay(),
                        Carbon::parse($date)->endOfDay()
                    ])
                    ->where('status', 'present')
                    ->count();

                // Check if attendance has been taken for this class today
                $hasAttendance = Attendance::where('class_id', $class->id)
                    ->whereBetween('created_at', [
                        Carbon::parse($date)->startOfDay(),
                        Carbon::parse($date)->endOfDay()
                    ])
                    ->exists();

                $teacherName = 'No Teacher Assigned';
                if ($class->teacherRecord) {
                    $teacherName = $class->teacherRecord->full_name;
                } else if ($class->teacher) {
                    $teacherName = $class->teacher->name;
                }

                return [
                    'id' => $class->id,
                    'className' => $class->name,
                    'teacher' => $teacherName,
                    'time' => $class->schedule_time ? $class->schedule_time : null,
                    'status' => $hasAttendance ? 'completed' : 'scheduled',
                    'presentCount' => $presentCount,
                    'totalStudents' => $class->getStudentsCountBySection(),
                    'classId' => $class->id,
                ];
            });
    }

    /**
     * Export attendance data to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'class_id' => 'nullable|exists:classes,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'nullable|in:class,system,teacher',
                'export_all' => 'nullable|boolean',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $format = $request->input('format', 'class');
            $exportAll = $request->boolean('export_all');

            // Get attendance data based on user role and request
            $attendanceData = $this->getAttendanceData($user, $request->class_id, $startDate, $endDate, $format, $exportAll);
            
            // Create filename
            $filename = $this->generateFilename($user, $request->class_id, $startDate, $endDate, $format);
            
            // Create CSV content instead of Excel
            $headers = [
                'Date', 'Time', 'Student Name', 'Student ID', 'Class', 'Status', 
                'Scanned At', 'Has Excuse', 'Excuse Status', 'Excuse Reason'
            ];
            
            $csvContent = implode(',', $headers) . "\n";
            
            foreach ($attendanceData as $attendance) {
                $row = [
                    $attendance['Date'],
                    $attendance['Time'],
                    '"' . $attendance['Student Name'] . '"',
                    '"' . $attendance['Student ID'] . '"',
                    '"' . $attendance['Class'] . '"',
                    $attendance['Status'],
                    $attendance['Scanned At'],
                    $attendance['Has Excuse'],
                    $attendance['Excuse Status'],
                    '"' . $attendance['Excuse Reason'] . '"'
                ];
                $csvContent .= implode(',', $row) . "\n";
            }
            
            // Change filename extension to CSV
            $filename = str_replace('.xlsx', '.csv', $filename);
            
            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get attendance data based on parameters
     */
    private function getAttendanceData($user, $classId, $startDate, $endDate, $format, $exportAll)
    {
        $query = Attendance::with(['student', 'class', 'excuse'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc');

        // Apply filters based on user role and request
        if ($user->role === 'admin' && $exportAll) {
            // Admin exporting all data - no additional filters
        } elseif ($classId) {
            // Specific class export
            $query->where('class_id', $classId);
            
            // Check permissions for teachers
            if ($user->role === 'teacher') {
                $class = ClassModel::find($classId);
                if (!$class || $class->teacher_id !== $user->id) {
                    throw new \Exception('Unauthorized to export this class data');
                }
            }
        } elseif ($user->role === 'teacher') {
            // Teacher exporting all their classes
            $teacherClasses = ClassModel::where('teacher_id', $user->id)->pluck('id');
            $query->whereIn('class_id', $teacherClasses);
        }

        return $query->get()->map(function ($attendance) {
            return [
                'Date' => $attendance->created_at->format('Y-m-d'),
                'Time' => $attendance->created_at->format('H:i:s'),
                'Student Name' => $attendance->student ? $attendance->student->name : 'N/A',
                'Student ID' => $attendance->student ? $attendance->student->student_id : 'N/A',
                'Class' => $attendance->class ? $attendance->class->name : 'N/A',
                'Status' => ucfirst($attendance->status),
                'Scanned At' => $attendance->scanned_at ? $attendance->scanned_at->format('H:i:s') : '',
                'Has Excuse' => $attendance->excuse ? 'Yes' : 'No',
                'Excuse Status' => $attendance->excuse ? ucfirst($attendance->excuse->status) : '',
                'Excuse Reason' => $attendance->excuse ? $attendance->excuse->reason : '',
            ];
        })->toArray();
    }
    
    /**
     * Generate filename for export
     */
    private function generateFilename($user, $classId, $startDate, $endDate, $format)
    {
        $dateRange = $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d');
        
        if ($classId) {
            $class = ClassModel::find($classId);
            $className = $class ? str_replace(' ', '_', $class->name) : 'class';
            return "attendance_{$className}_{$dateRange}.csv";
        } elseif ($user->role === 'admin' && $format === 'system') {
            return "system_attendance_{$dateRange}.csv";
        } else {
            $teacherName = $user->name ? str_replace(' ', '_', $user->name) : 'teacher';
            return "attendance_{$teacherName}_classes_{$dateRange}.csv";
        }
    }

    /**
     * Enroll a student in a class
     */
    public function enrollStudent(Request $request)
    {
        $user = Auth::user();
        
        // Only admins and teachers can enroll students
        if (!in_array($user->role, ['admin', 'teacher'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $student = Student::find($validated['student_id']);
        $class = ClassModel::find($validated['class_id']);

        // If teacher, ensure they can only enroll students in their own classes
        if ($user->role === 'teacher' && $class->teacher_id !== $user->id) {
            return response()->json(['error' => 'You can only enroll students in your own classes'], 403);
        }

        // Check if student is already enrolled
        if ($student->classes()->where('class_id', $class->id)->exists()) {
            return response()->json(['error' => 'Student is already enrolled in this class'], 409);
        }

        // Enroll the student
        $student->classes()->attach($class->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        // Also set the primary class_id if not set
        if (!$student->class_id) {
            $student->update(['class_id' => $class->id]);
        }

        return response()->json([
            'message' => 'Student enrolled successfully',
            'student' => $student->load('classes'),
        ]);
    }

    /**
     * Remove a student from a class
     */
    public function unenrollStudent(Request $request)
    {
        $user = Auth::user();
        
        // Only admins and teachers can unenroll students
        if (!in_array($user->role, ['admin', 'teacher'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $student = Student::find($validated['student_id']);
        $class = ClassModel::find($validated['class_id']);

        // If teacher, ensure they can only unenroll students from their own classes
        if ($user->role === 'teacher' && $class->teacher_id !== $user->id) {
            return response()->json(['error' => 'You can only unenroll students from your own classes'], 403);
        }

        // Check if student is enrolled
        $enrollment = $student->classes()->where('class_id', $class->id)->first();
        if (!$enrollment) {
            return response()->json(['error' => 'Student is not enrolled in this class'], 404);
        }

        // Mark as inactive and set dropped date
        $student->classes()->updateExistingPivot($class->id, [
            'is_active' => false,
            'dropped_at' => now(),
        ]);

        return response()->json([
            'message' => 'Student unenrolled successfully',
            'student' => $student->load('activeClasses'),
        ]);
    }

    /**
     * Get students for a specific class with enrollment info
     */
    public function getClassStudentsWithEnrollment($classId)
    {
        $user = Auth::user();
        $class = ClassModel::find($classId);

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        // Check permissions
        if ($user->role === 'teacher' && $class->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get students via multiple methods for comprehensive view
        $enrolledStudents = $class->activeStudents()->get()->map(function ($student) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'email' => $student->email,
                'course' => $student->course,
                'section' => $student->section,
                'enrollment_method' => 'enrolled',
                'enrolled_at' => $student->pivot->enrolled_at,
                'is_active' => $student->pivot->is_active,
            ];
        });

        // Get students by course/section matching
        $sectionStudents = $class->getStudentsBySection()->map(function ($student) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'email' => $student->email,
                'course' => $student->course,
                'section' => $student->section,
                'enrollment_method' => 'section_match',
                'enrolled_at' => null,
                'is_active' => true,
            ];
        });

        // Combine and remove duplicates
        $allStudents = $enrolledStudents->concat($sectionStudents)
            ->unique('id')
            ->values();

        return response()->json([
            'class' => $class,
            'students' => $allStudents,
            'enrolled_count' => $enrolledStudents->count(),
            'section_match_count' => $sectionStudents->count(),
            'total_students' => $allStudents->count(),
        ]);
    }

    /**
     * Bulk enroll students by course and section
     */
    public function bulkEnrollBySection(Request $request)
    {
        $user = Auth::user();
        
        // Only admins can do bulk enrollment
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Only admins can perform bulk enrollment'], 403);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course' => 'required|string',
            'section' => 'required|string',
        ]);

        $class = ClassModel::find($validated['class_id']);
        
        // Find all students matching the course and section
        $students = Student::where('course', $validated['course'])
                          ->where('section', $validated['section'])
                          ->get();

        $enrolledCount = 0;
        $skippedCount = 0;

        foreach ($students as $student) {
            // Check if already enrolled
            if (!$student->classes()->where('class_id', $class->id)->exists()) {
                $student->classes()->attach($class->id, [
                    'is_active' => true,
                    'enrolled_at' => now(),
                ]);
                
                // Set primary class_id if not set
                if (!$student->class_id) {
                    $student->update(['class_id' => $class->id]);
                }
                
                $enrolledCount++;
            } else {
                $skippedCount++;
            }
        }

        return response()->json([
            'message' => 'Bulk enrollment completed',
            'enrolled_count' => $enrolledCount,
            'skipped_count' => $skippedCount,
            'total_students' => $students->count(),
        ]);
    }
}
