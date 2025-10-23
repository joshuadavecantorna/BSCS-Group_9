<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }
        
        $path = $request->path();
        
        // Check if user is a teacher accessing admin routes
        if ($user->isTeacher()) {
            // Redirect teachers from admin routes to teacher routes
            if ($path === 'dashboard') {
                return redirect()->route('teacher.dashboard');
            }
            if ($path === 'files') {
                return redirect()->route('teacher.files');
            }
            if ($path === 'reports') {
                return redirect()->route('teacher.reports');
            }
        }
        
        // Check if user is a student accessing admin routes
        if ($user->role === 'student') {
            // Redirect students from admin routes to student routes
            if ($path === 'dashboard') {
                return redirect()->route('student.dashboard');
            }
            if ($path === 'files') {
                return redirect()->route('student.classes');
            }
        }
        
        return $next($request);
    }
}