<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create a student record for the new user (since default role is 'student')
        if ($user->role === 'student') {
            // Check if a student record with this email already exists
            $existingStudent = \App\Models\Student::where('email', $user->email)->first();
            
            if ($existingStudent && !$existingStudent->user_id) {
                // If student exists but has no user_id, link them
                $existingStudent->update(['user_id' => $user->id]);
            } elseif (!$existingStudent) {
                // If no student record exists, create one
                \App\Models\Student::create([
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

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
