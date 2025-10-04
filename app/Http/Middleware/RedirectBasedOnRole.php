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
        
        // Check if user is a teacher accessing admin routes
        if ($user->isTeacher()) {
            $path = $request->path();
            
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
        
        return $next($request);
    }
}