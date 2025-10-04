<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Show the student dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('student/Dashboard');
    }

    /**
     * Show the student classes page.
     */
    public function classes(): Response
    {
        return Inertia::render('student/Classes');
    }

    /**
     * Show the student attendance history page.
     */
    public function attendanceHistory(): Response
    {
        return Inertia::render('student/AttendanceHistory');
    }

    /**
     * Show the student excuse requests page.
     */
    public function excuseRequests(): Response
    {
        return Inertia::render('student/ExcuseRequests');
    }
}
