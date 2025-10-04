<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Debug route to test attendance data
Route::get('debug-attendance', function () {
    $user = Auth::user();
    $controller = new App\Http\Controllers\AttendanceController();
    
    if (!$user) {
        // Login as teacher ID 2 for testing
        Auth::loginUsingId(2);
        $user = Auth::user();
    }
    
    $data = $controller->index(request());
    return response()->json([
        'current_user' => $user,
        'controller_data' => $data->getData()
    ]);
});

// Attendance Management Routes
Route::middleware(['auth'])->group(function () {
    // Main attendance page (role-based view)
    Route::get('attendance', [App\Http\Controllers\AttendanceController::class, 'index'])
        ->name('attendance');

    // Teacher and Admin routes
    Route::middleware(['role:teacher,admin'])->group(function () {
        Route::post('attendance/record', [App\Http\Controllers\AttendanceController::class, 'recordAttendance'])
            ->name('attendance.record');
        Route::get('attendance/class/{classId}/students', [App\Http\Controllers\AttendanceController::class, 'getClassStudents'])
            ->name('attendance.class.students');
        Route::get('attendance/class/{classId}/students-enrollment', [App\Http\Controllers\AttendanceController::class, 'getClassStudentsWithEnrollment'])
            ->name('attendance.class.students.enrollment');
        Route::post('attendance/export', [App\Http\Controllers\AttendanceController::class, 'exportToExcel'])
            ->name('attendance.export');
        
        // Student enrollment management
        Route::post('attendance/enroll-student', [App\Http\Controllers\AttendanceController::class, 'enrollStudent'])
            ->name('attendance.enroll.student');
        Route::post('attendance/unenroll-student', [App\Http\Controllers\AttendanceController::class, 'unenrollStudent'])
            ->name('attendance.unenroll.student');
        
        // Excuse management routes
        Route::get('attendance/excuses', [App\Http\Controllers\AttendanceController::class, 'manageExcuses'])
            ->name('attendance.excuses.manage');
        Route::post('attendance/excuses/{excuse}/approve', [App\Http\Controllers\AttendanceController::class, 'approveExcuse'])
            ->name('attendance.excuses.approve');
        Route::post('attendance/excuses/{excuse}/reject', [App\Http\Controllers\AttendanceController::class, 'rejectExcuse'])
            ->name('attendance.excuses.reject');
        
        // QR Session management routes
        Route::post('attendance/qr/start', [App\Http\Controllers\AttendanceController::class, 'startQRSession'])
            ->name('attendance.qr.start');
        Route::post('attendance/qr/end', [App\Http\Controllers\AttendanceController::class, 'endQRSession'])
            ->name('attendance.qr.end');
        Route::get('attendance/qr/sessions', [App\Http\Controllers\AttendanceController::class, 'getActiveQRSessions'])
            ->name('attendance.qr.sessions');
    });

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('attendance/system-overview', [App\Http\Controllers\AttendanceController::class, 'getSystemOverview'])
            ->name('attendance.system.overview');
        
        // Bulk enrollment (admin only)
        Route::post('attendance/bulk-enroll', [App\Http\Controllers\AttendanceController::class, 'bulkEnrollBySection'])
            ->name('attendance.bulk.enroll');
    });

        // Student routes
    Route::middleware(['role:student'])->group(function () {
        Route::post('attendance/excuse', [App\Http\Controllers\AttendanceController::class, 'submitExcuse'])
            ->name('attendance.excuse.submit');
        Route::get('attendance/my-records', [App\Http\Controllers\AttendanceController::class, 'getStudentAttendance'])
            ->name('attendance.student.records');
        
        // Student QR scan route
        Route::post('attendance/qr/scan', [App\Http\Controllers\AttendanceController::class, 'scan'])
            ->name('attendance.qr.scan');
    });
});

Route::get('reports', function () {
    return Inertia::render('Reports');
})->name('reports');

Route::get('files', [App\Http\Controllers\FilesController::class, 'index'])
    ->name('files');

Route::post('files', [App\Http\Controllers\FilesController::class, 'store']);

Route::get('files/download/{filename}', [App\Http\Controllers\FilesController::class, 'download'])
    ->name('files.download');

Route::get('files/{filename}', [App\Http\Controllers\FilesController::class, 'download'])
    ->name('files.view');

Route::delete('files/{filename}', [App\Http\Controllers\FilesController::class, 'destroy'])
    ->name('files.destroy');

Route::post('files/{filename}/share', [App\Http\Controllers\FilesController::class, 'share'])
    ->name('files.share');

Route::get('reports', function () {
    return Inertia::render('Reports');
})->middleware(['auth', 'verified'])->name('reports');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
