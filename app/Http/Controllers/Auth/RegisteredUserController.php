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
     *
     * @throws \Illuminate\Validation\ValidationException
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
            'role' => 'required|string|in:student,teacher',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'role' => $user->role,
            'email' => $user->email
        ]);

        // Create appropriate record based on role
        if ($user->role === 'student') {
            // Check if a student record with this email already exists
            $existingStudent = Student::where('email', $user->email)->first();
            
            if ($existingStudent && !$existingStudent->user_id) {
                // If student exists but has no user_id, link them
                $existingStudent->update(['user_id' => $user->id]);
                Log::info('Linked existing student record', ['student_id' => $existingStudent->id]);
            } elseif (!$existingStudent) {
                // If no student record exists, create one
                $student = Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => 'TEMP-' . $user->id,
                    'course' => 'Not Set',
                    'year' => '1st Year',
                    'section' => 'A'
                ]);
                Log::info('Created new student record', ['student_id' => $student->id]);
            }
        } elseif ($user->role === 'teacher') {
            // Check if a teacher record with this email already exists
            $existingTeacher = Teacher::where('email', $user->email)->first();
            
            if ($existingTeacher && !$existingTeacher->user_id) {
                // If teacher exists but has no user_id, link them
                $existingTeacher->update(['user_id' => $user->id]);
                Log::info('Linked existing teacher record', ['teacher_id' => $existingTeacher->id]);
            } elseif (!$existingTeacher) {
                // If no teacher record exists, create one
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'employee_id' => 'TEMP-' . $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'department' => $request->school_name ?? 'Not Set',
                    'position' => 'Teacher',
                    'is_active' => true
                ]);
                Log::info('Created new teacher record', ['teacher_id' => $teacher->id]);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
