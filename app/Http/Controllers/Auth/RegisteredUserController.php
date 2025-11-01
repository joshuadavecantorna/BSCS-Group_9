<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Registration request received', [
            'request_data' => $request->all(),
            'role' => $request->role
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:student,teacher',
            'class_code' => 'nullable|string|max:255',
            'school_name' => 'nullable|string|max:255',
        ]);

        // Use database transaction to ensure data consistency
        DB::transaction(function () use ($request) {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // Create role-specific record
            if ($request->role === 'student') {
                $this->createStudentRecord($user, $request);
            } elseif ($request->role === 'teacher') {
                $this->createTeacherRecord($user, $request);
            }

            event(new Registered($user));
            Auth::login($user);
        });

        return to_route('dashboard');
    }

    /**
     * Create student record for the user
     */
    private function createStudentRecord(User $user, Request $request): void
    {
        // Generate unique student ID
        $studentId = $this->generateStudentId();
        
        // Extract name parts
        $nameParts = explode(' ', trim($user->name));
        
        // Determine year level and course based on class code or defaults
        $yearLevel = '1st Year';
        $course = 'Computer Science';
        $section = 'A';

        if ($request->class_code) {
            $this->parseClassCode($request->class_code, $yearLevel, $course, $section);
        }

        // Create student with explicit PostgreSQL boolean handling
        $student = new Student();
        $student->student_id = $studentId;
        $student->name = $user->name;
        $student->email = $user->email;
        $student->year = $yearLevel;
        $student->course = $course;
        $student->section = $section;
        $student->qr_data = [
            'student_id' => $studentId,
            'name' => $user->name,
            'email' => $user->email,
            'year' => $yearLevel,
            'course' => $course,
            'section' => $section,
        ];
        $student->is_active = true;
        $student->save();
    }

    /**
     * Create teacher record for the user
     */
    private function createTeacherRecord(User $user, Request $request): void
    {
        // Generate unique teacher ID
        $teacherId = $this->generateTeacherId();
        
        // Extract name parts
        $nameParts = explode(' ', trim($user->name));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : '';
        $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';

        Teacher::create([
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'email' => $user->email,
            'phone' => null,
            'department' => 'Computer Science',
            'position' => 'Instructor',
            'is_active' => true,
        ]);
    }

    /**
     * Generate unique student ID
     */
    private function generateStudentId(): string
    {
        $year = date('Y');
        $lastStudent = Student::where('student_id', 'like', "STU-{$year}-%")
            ->orderBy('student_id', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_id, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('STU-%s-%03d', $year, $newNumber);
    }

    /**
     * Generate unique teacher ID
     */
    private function generateTeacherId(): string
    {
        $lastTeacher = Teacher::orderBy('teacher_id', 'desc')->first();

        if ($lastTeacher && preg_match('/TEACH-(\d+)/', $lastTeacher->teacher_id, $matches)) {
            $lastNumber = (int) $matches[1];
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('TEACH-%03d', $newNumber);
    }

    /**
     * Parse class code to determine academic details
     */
    private function parseClassCode(string $classCode, &$yearLevel, &$course, &$section): void
    {
        $classCode = strtoupper(trim($classCode));
        
        // Pattern: CS101-A (Course + Level + Section)
        if (preg_match('/^([A-Z]+)(\d+)(?:-([A-Z]))$/', $classCode, $matches)) {
            $courseCode = $matches[1];
            $level = (int) $matches[2];
            $sectionCode = $matches[3] ?? 'A';
            
            // Map course codes
            $courseMap = [
                'CS' => 'Computer Science',
                'IT' => 'Information Technology',
                'IS' => 'Information Systems',
                'CE' => 'Computer Engineering',
            ];
            
            if (isset($courseMap[$courseCode])) {
                $course = $courseMap[$courseCode];
            }
            
            // Determine year level from course number
            if ($level >= 100 && $level < 200) {
                $yearLevel = '1st Year';
            } elseif ($level >= 200 && $level < 300) {
                $yearLevel = '2nd Year';
            } elseif ($level >= 300 && $level < 400) {
                $yearLevel = '3rd Year';
            } elseif ($level >= 400) {
                $yearLevel = '4th Year';
            }
            
            $section = $sectionCode;
        }
    }
}
