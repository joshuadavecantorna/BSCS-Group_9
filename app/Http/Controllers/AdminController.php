<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function dashboard()
    {
        // System overview stats
        $stats = [
            'total_students' => Student::count(),
            'total_teachers' => Teacher::count(),
            'total_classes' => ClassModel::count(),
            'active_sessions' => AttendanceSession::where('status', 'active')->count(),
        ];

        // Global attendance stats
        $attendanceStats = [
            'overall_present_percentage' => $this->calculateOverallAttendance(),
            'total_absences' => $this->getTotalAbsences(),
            'total_excuses' => $this->getTotalExcuses(),
        ];

        // Recent activity logs
        $recentActivity = [
            [
                'id' => 1,
                'type' => 'teacher_created',
                'message' => 'New teacher account created: Dr. Jane Smith',
                'timestamp' => now()->subHours(2)->toISOString(),
                'user' => 'Admin User'
            ],
            [
                'id' => 2,
                'type' => 'class_created',
                'message' => 'New class created: Advanced Mathematics - BSCS-A',
                'timestamp' => now()->subDays(1)->toISOString(),
                'user' => 'Prof. John Smith'
            ],
            [
                'id' => 3,
                'type' => 'system_update',
                'message' => 'System settings updated: Authentication rules modified',
                'timestamp' => now()->subDays(2)->toISOString(),
                'user' => 'Admin User'
            ]
        ];

        return Inertia::render('AdminDashboard', [
            'stats' => $stats,
            'attendanceStats' => $attendanceStats,
            'recentActivity' => $recentActivity
        ]);
    }

    /**
     * Display teachers management page
     */
    public function teachers()
    {
        $teachers = Teacher::with('user')
            ->select('teachers.*')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'employee_id' => $teacher->employee_id,
                    'name' => $teacher->first_name . ' ' . $teacher->last_name,
                    'email' => $teacher->email,
                    'phone' => $teacher->phone,
                    'department' => $teacher->department,
                    'position' => $teacher->position,
                    'is_active' => $teacher->is_active,
                    'created_at' => $teacher->created_at->toISOString(),
                    'classes_count' => ClassModel::where('teacher_id', $teacher->id)->count(),
                ];
            });

        return Inertia::render('Admin/Teachers', [
            'teachers' => $teachers
        ]);
    }

    /**
     * Store a new teacher
     */
    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'required|string|unique:teachers,employee_id',
            'department' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'teacher',
                'email_verified_at' => now(),
            ]);

            // Create teacher profile
            Teacher::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'department' => $validated['department'],
                'position' => $validated['position'],
                'is_active' => true,
            ]);

            DB::commit();
            return redirect()->route('admin.teachers')->with('success', 'Teacher created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Update teacher
     */
    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'required|string|unique:teachers,employee_id,' . $id,
            'department' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update user account
            $teacher->user->update([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
            ]);

            // Update teacher profile
            $teacher->update([
                'employee_id' => $validated['employee_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'department' => $validated['department'],
                'position' => $validated['position'],
            ]);

            DB::commit();
            return redirect()->route('admin.teachers')->with('success', 'Teacher updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle teacher active status
     */
    public function toggleTeacherStatus($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->update([
            'is_active' => !$teacher->is_active
        ]);

        $status = $teacher->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.teachers')->with('success', "Teacher {$status} successfully!");
    }

    /**
     * Delete teacher (soft delete)
     */
    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Deactivate instead of deleting to preserve data integrity
            $teacher->update(['is_active' => false]);
            // Optionally delete the user account as well
            // $teacher->user->delete();
            
            DB::commit();
            return redirect()->route('admin.teachers')->with('success', 'Teacher deactivated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Display students management page
     */
    public function students()
    {
        $students = Student::with('user')
            ->select('students.*')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'year' => $student->year,
                    'course' => $student->course,
                    'section' => $student->section,
                    'is_active' => $student->is_active ?? true,
                    'created_at' => $student->created_at->toISOString(),
                    'classes_count' => DB::table('class_student')->where('student_id', $student->id)->count(),
                ];
            });

        return Inertia::render('Admin/Students', [
            'students' => $students
        ]);
    }

    /**
     * Store a new student
     */
    public function storeStudent(Request $request)
    {
        Log::info('=== STORE STUDENT REQUEST ===');
        Log::info('Request data:', $request->all());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'student_id' => 'required|string|unique:students,student_id',
            'year' => 'required|string|max:50',
            'course' => 'required|string|max:255',
            'section' => 'required|string|max:10',
            'password' => 'required|string|min:8|confirmed',
        ]);

        Log::info('Validation passed:', $validated);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'student',
                'email_verified_at' => now(),
            ]);
            
            Log::info('User created with ID:', ['user_id' => $user->id]);

            // Create student profile
            $student = Student::create([
                'user_id' => $user->id,
                'student_id' => $validated['student_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'year' => $validated['year'],
                'course' => $validated['course'],
                'section' => $validated['section'],
                'is_active' => true,
            ]);
            
            Log::info('Student created with ID:', ['student_id' => $student->id]);

            DB::commit();
            Log::info('Transaction committed successfully');
            
            return redirect()->route('admin.students')->with('success', 'Student created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create student:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to create student: ' . $e->getMessage()]);
        }
    }

    /**
     * Update student
     */
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($student->user_id ?? 'NULL'),
            'phone' => 'nullable|string|max:20',
            'student_id' => 'required|string|unique:students,student_id,' . $id,
            'year' => 'required|string|max:50',
            'course' => 'required|string|max:255',
            'section' => 'required|string|max:10',
        ]);

        DB::beginTransaction();
        try {
            // Update user account if it exists
            if ($student->user) {
                $student->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]);
            }

            // Update student profile
            $student->update([
                'student_id' => $validated['student_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'year' => $validated['year'],
                'course' => $validated['course'],
                'section' => $validated['section'],
            ]);

            DB::commit();
            return redirect()->route('admin.students')->with('success', 'Student updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update student: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle student active status
     */
    public function toggleStudentStatus($id)
    {
        Log::info('=== TOGGLE STUDENT STATUS REQUEST ===');
        Log::info('Student ID:', ['id' => $id]);
        
        $student = Student::findOrFail($id);
        $currentStatus = $student->is_active ?? true;
        $newStatus = !$currentStatus;
        
        Log::info('Status change:', [
            'student_id' => $student->id,
            'name' => $student->name,
            'current_status' => $currentStatus,
            'new_status' => $newStatus
        ]);
        
        $student->update([
            'is_active' => $newStatus
        ]);
        
        Log::info('Status updated successfully');

        $status = $newStatus ? 'activated' : 'deactivated';
        return redirect()->route('admin.students')->with('success', "Student {$status} successfully!");
    }

    /**
     * Delete student (soft delete)
     */
    public function deleteStudent($id)
    {
        Log::info('=== DELETE STUDENT REQUEST ===');
        Log::info('Student ID to delete:', ['id' => $id]);
        
        $student = Student::findOrFail($id);
        
        Log::info('Student found:', [
            'student_id' => $student->id,
            'name' => $student->name,
            'current_status' => $student->is_active
        ]);
        
        DB::beginTransaction();
        try {
            // Deactivate instead of deleting to preserve data integrity
            $student->update(['is_active' => false]);
            
            Log::info('Student deactivated successfully');
            
            // Optionally delete the user account as well
            // $student->user->delete();
            
            DB::commit();
            Log::info('Transaction committed');
            
            return redirect()->route('admin.students')->with('success', 'Student deactivated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete student:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to delete student: ' . $e->getMessage()]);
        }
    }

    /**
     * Display admin settings page
     */
    public function settings()
    {
        $settings = [
            'school_info' => [
                'name' => config('app.name'),
                'address' => '123 University Ave, Education City',
                'phone' => '+1 (555) 123-4567',
                'email' => 'admin@university.edu',
            ],
            'authentication' => [
                'require_email_verification' => true,
                'password_min_length' => 8,
                'session_timeout' => 120, // minutes
            ],
            'attendance' => [
                'auto_mark_absent_after' => 15, // minutes
                'allow_late_submissions' => true,
                'require_excuse_for_absence' => true,
            ]
        ];

        return Inertia::render('Admin/Settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Calculate overall attendance percentage
     */
    private function calculateOverallAttendance()
    {
        // Mock calculation - replace with actual logic
        $totalSessions = AttendanceSession::count();
        $totalPresent = DB::table('attendance_records')
            ->where('status', 'present')
            ->count();
        $totalRecords = DB::table('attendance_records')->count();

        return $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0;
    }

    /**
     * Get total absences
     */
    private function getTotalAbsences()
    {
        return DB::table('attendance_records')
            ->where('status', 'absent')
            ->count();
    }

    /**
     * Get total excuses
     */
    private function getTotalExcuses()
    {
        return DB::table('attendance_records')
            ->where('status', 'excused')
            ->count();
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_address' => 'required|string|max:500',
            'school_email' => 'required|email|max:255',
            'school_phone' => 'required|string|max:20',
            'academic_year' => 'required|string|max:20',
            'semester' => 'required|string|in:1st Semester,2nd Semester,Summer',
            'attendance_grace_period' => 'required|integer|min:0|max:60',
            'late_threshold' => 'required|integer|min:1|max:30',
            'auto_end_sessions' => 'required|boolean',
            'session_duration' => 'required|integer|min:5|max:240',
            'require_location' => 'required|boolean',
            'allow_makeup_attendance' => 'required|boolean',
            'notification_email' => 'required|email|max:255',
            'enable_sms' => 'required|boolean',
            'backup_frequency' => 'required|string|in:daily,weekly,monthly',
            'enable_reports' => 'required|boolean',
        ]);

        try {
            // In a real application, you would save these to a settings table
            // For now, we'll simulate success
            
            return redirect()->route('admin.settings')->with('success', 'Settings updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Display admin reports page
     */
    public function reports(Request $request)
    {
        // Get filters from request
        $filters = [
            'department' => $request->get('department'),
            'teacher_id' => $request->get('teacher_id'),
            'class_id' => $request->get('class_id'),
            'student_id' => $request->get('student_id'),
            'course' => $request->get('course'),
            'section' => $request->get('section'),
            'year' => $request->get('year'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
        ];

        // Build attendance records query with real database data
        $attendanceQuery = DB::table('attendance_records as ar')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('class_models as c', 'ats.class_id', '=', 'c.id')
            ->join('teachers as t', 'ats.teacher_id', '=', 't.id')
            ->select([
                'ar.*',
                's.name as student_name',
                's.student_id as student_number',
                's.course as student_course',
                's.section as student_section',
                's.year as student_year',
                's.email as student_email',
                't.first_name as teacher_first_name',
                't.last_name as teacher_last_name',
                't.department',
                'c.name as class_name',
                'c.course as class_course',
                'ats.session_date',
                'ats.start_time',
                'ats.end_time',
                'ats.session_name'
            ]);

        // Apply filters
        if ($filters['department']) {
            $attendanceQuery->where('t.department', $filters['department']);
        }
        if ($filters['teacher_id']) {
            $attendanceQuery->where('t.id', $filters['teacher_id']);
        }
        if ($filters['class_id']) {
            $attendanceQuery->where('c.id', $filters['class_id']);
        }
        if ($filters['student_id']) {
            $attendanceQuery->where('s.id', $filters['student_id']);
        }
        if ($filters['course']) {
            $attendanceQuery->where('s.course', $filters['course']);
        }
        if ($filters['section']) {
            $attendanceQuery->where('s.section', $filters['section']);
        }
        if ($filters['year']) {
            $attendanceQuery->where('s.year', $filters['year']);
        }
        if ($filters['date_from']) {
            $attendanceQuery->where('ats.session_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $attendanceQuery->where('ats.session_date', '<=', $filters['date_to']);
        }
        if ($filters['status']) {
            $attendanceQuery->where('ar.status', $filters['status']);
        }

        // Get paginated results
        $attendanceRecords = $attendanceQuery
            ->orderBy('ats.session_date', 'desc')
            ->orderBy('ats.start_time', 'desc')
            ->paginate(50);

        // Get summary stats based on filtered data
        $summaryStats = [
            'total' => $attendanceQuery->count(),
            'present' => (clone $attendanceQuery)->where('ar.status', 'present')->count(),
            'absent' => (clone $attendanceQuery)->where('ar.status', 'absent')->count(),
            'excused' => (clone $attendanceQuery)->where('ar.status', 'excused')->count(),
            'late' => (clone $attendanceQuery)->where('ar.status', 'late')->count(),
        ];

        // Student statistics
        $studentStats = [
            'total_students' => Student::count(),
            'active_students' => Student::whereRaw('COALESCE(is_active, true) = true')->count(),
            'students_by_course' => Student::select('course', DB::raw('COUNT(*) as count'))
                ->groupBy('course')
                ->orderBy('count', 'desc')
                ->get(),
            'students_by_year' => Student::select('year', DB::raw('COUNT(*) as count'))
                ->groupBy('year')
                ->orderBy('year')
                ->get(),
        ];

        // Get departments for filter
        $departments = Teacher::distinct('department')
            ->whereNotNull('department')
            ->pluck('department')
            ->filter();

        // Get teachers for filter
        $teachers = Teacher::select('id', 'first_name', 'last_name', 'department')
                          ->whereRaw('COALESCE(is_active, true) = true')
                          ->orderBy('first_name')
                          ->get()
                          ->map(function ($teacher) {
                              return [
                                  'id' => $teacher->id,
                                  'name' => $teacher->first_name . ' ' . $teacher->last_name,
                                  'department' => $teacher->department,
                              ];
                          });

        // Get classes for filter
        $classes = DB::table('class_models as c')
                   ->join('teachers as t', 'c.teacher_id', '=', 't.id')
                   ->select('c.id', 'c.name', 'c.course', 't.first_name', 't.last_name')
                   ->orderBy('c.name')
                   ->get()
                   ->map(function ($class) {
                       return [
                           'id' => $class->id,
                           'name' => $class->name,
                           'course' => $class->course,
                           'teacher' => $class->first_name . ' ' . $class->last_name
                       ];
                   });

        // Get students for filter
        $students = Student::select('id', 'name', 'student_id', 'course', 'section', 'year')
                          ->whereRaw('COALESCE(is_active, true) = true')
                          ->orderBy('name')
                          ->get()
                          ->map(function ($student) {
                              return [
                                  'id' => $student->id,
                                  'name' => $student->name,
                                  'student_id' => $student->student_id,
                                  'course' => $student->course,
                                  'section' => $student->section,
                                  'year' => $student->year,
                              ];
                          });

        // Get distinct courses and sections for filters
        $courses = Student::distinct('course')->whereNotNull('course')->pluck('course')->filter();
        $sections = Student::distinct('section')->whereNotNull('section')->pluck('section')->filter();
        $years = Student::distinct('year')->whereNotNull('year')->pluck('year')->filter();

        return Inertia::render('Admin/AdminReports', [
            'summaryStats' => $summaryStats,
            'studentStats' => $studentStats,
            'attendanceRecords' => $attendanceRecords,
            'departments' => $departments,
            'teachers' => $teachers,
            'classes' => $classes,
            'students' => $students,
            'courses' => $courses,
            'sections' => $sections,
            'years' => $years,
            'filters' => $filters,
        ]);
    }

    /**
     * Export attendance report as PDF
     */
    public function exportReportPDF(Request $request)
    {
        // Get the same filtered data as the reports page
        $filters = $request->all();
        
        $attendanceQuery = DB::table('attendance_records as ar')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('classes as c', 'ats.class_id', '=', 'c.id')
            ->join('teachers as t', 'ats.teacher_id', '=', 't.id')
            ->select([
                'ar.*',
                's.name as student_name',
                's.student_id as student_number',
                't.first_name as teacher_first_name',
                't.last_name as teacher_last_name',
                't.department',
                'c.name as class_name',
                'c.course',
                'ats.session_date',
                'ats.start_time',
                'ats.end_time'
            ]);

        // Apply same filters as reports page
        if ($filters['department'] ?? null) {
            $attendanceQuery->where('t.department', $filters['department']);
        }
        if ($filters['teacher_id'] ?? null) {
            $attendanceQuery->where('t.id', $filters['teacher_id']);
        }
        if ($filters['class_id'] ?? null) {
            $attendanceQuery->where('c.id', $filters['class_id']);
        }
        if ($filters['date_from'] ?? null) {
            $attendanceQuery->where('ats.session_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] ?? null) {
            $attendanceQuery->where('ats.session_date', '<=', $filters['date_to']);
        }
        if ($filters['status'] ?? null) {
            $attendanceQuery->where('ar.status', $filters['status']);
        }

        $attendanceRecords = $attendanceQuery
            ->orderBy('ats.session_date', 'desc')
            ->orderBy('ats.start_time', 'desc')
            ->get();

        $summaryStats = [
            'total' => $attendanceRecords->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'excused' => $attendanceRecords->where('status', 'excused')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.attendance-pdf', [
            'attendanceRecords' => $attendanceRecords,
            'summaryStats' => $summaryStats,
            'filters' => $filters,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download('attendance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Email attendance report
     */
    public function emailReport(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'department' => 'nullable|string',
            'teacher_id' => 'nullable|integer',
            'class_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        try {
            // Generate the same PDF
            $filters = $request->except('email');
            
            $attendanceQuery = DB::table('attendance_records as ar')
                ->join('students as s', 'ar.student_id', '=', 's.id')
                ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
                ->join('classes as c', 'ats.class_id', '=', 'c.id')
                ->join('teachers as t', 'ats.teacher_id', '=', 't.id')
                ->select([
                    'ar.*',
                    's.name as student_name',
                    's.student_id as student_number',
                    't.first_name as teacher_first_name',
                    't.last_name as teacher_last_name',
                    't.department',
                    'c.name as class_name',
                    'c.course',
                    'ats.session_date',
                    'ats.start_time',
                    'ats.end_time'
                ]);

            // Apply filters
            if ($filters['department'] ?? null) {
                $attendanceQuery->where('t.department', $filters['department']);
            }
            if ($filters['teacher_id'] ?? null) {
                $attendanceQuery->where('t.id', $filters['teacher_id']);
            }
            if ($filters['class_id'] ?? null) {
                $attendanceQuery->where('c.id', $filters['class_id']);
            }
            if ($filters['date_from'] ?? null) {
                $attendanceQuery->where('ats.session_date', '>=', $filters['date_from']);
            }
            if ($filters['date_to'] ?? null) {
                $attendanceQuery->where('ats.session_date', '<=', $filters['date_to']);
            }
            if ($filters['status'] ?? null) {
                $attendanceQuery->where('ar.status', $filters['status']);
            }

            $attendanceRecords = $attendanceQuery
                ->orderBy('ats.session_date', 'desc')
                ->orderBy('ats.start_time', 'desc')
                ->get();

            $summaryStats = [
                'total' => $attendanceRecords->count(),
                'present' => $attendanceRecords->where('status', 'present')->count(),
                'absent' => $attendanceRecords->where('status', 'absent')->count(),
                'excused' => $attendanceRecords->where('status', 'excused')->count(),
                'late' => $attendanceRecords->where('status', 'late')->count(),
            ];

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('reports.attendance-pdf', [
                'attendanceRecords' => $attendanceRecords,
                'summaryStats' => $summaryStats,
                'filters' => $filters,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
            ]);

            // Send email with PDF attachment
            Mail::send('emails.attendance-report', [
                'summaryStats' => $summaryStats,
                'filters' => $filters,
            ], function ($message) use ($validated, $pdf) {
                $message->to($validated['email'])
                       ->subject('Attendance Report - ' . now()->format('Y-m-d'))
                       ->attachData($pdf->output(), 'attendance-report-' . now()->format('Y-m-d') . '.pdf');
            });

            return redirect()->route('admin.reports')->with('success', 'Report sent successfully to ' . $validated['email']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to send report: ' . $e->getMessage()]);
        }
    }
}