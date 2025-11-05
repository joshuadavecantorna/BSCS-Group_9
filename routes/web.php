<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('reports', function () {
    return Inertia::render('Reports');
})->middleware(['auth', 'verified'])->name('reports');
// Add this route ▼
Route::get('/teacher/files', function () {
    return view('teacher/files');
})->name('teacher.files');

// Teacher Routes
Route::middleware(['auth', 'verified'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/api', [App\Http\Controllers\TeacherController::class, 'dashboardApi'])->name('dashboard.api');
    Route::get('/classes', [App\Http\Controllers\TeacherController::class, 'classes'])->name('classes');
    Route::get('/attendance', [App\Http\Controllers\TeacherController::class, 'attendance'])->name('attendance');
    Route::get('/reports', [App\Http\Controllers\TeacherController::class, 'reports'])->name('reports');
    Route::get('/files', [App\Http\Controllers\TeacherController::class, 'files'])->name('files');
    Route::get('/excuse-requests', [App\Http\Controllers\TeacherController::class, 'excuseRequests'])->name('excuse-requests');
    Route::get('/settings', [App\Http\Controllers\TeacherController::class, 'settings'])->name('settings');
    
    // Class Management
    Route::post('/classes', [App\Http\Controllers\TeacherController::class, 'createClass']);
    Route::get('/classes/{id}', [App\Http\Controllers\TeacherController::class, 'getClass']);
    Route::put('/classes/{id}', [App\Http\Controllers\TeacherController::class, 'updateClass']);
    Route::delete('/classes/{id}', [App\Http\Controllers\TeacherController::class, 'deleteClass']);
    
    // Student Management
    Route::get('/students/search', [App\Http\Controllers\TeacherController::class, 'searchStudents']);
    Route::post('/classes/{classId}/students', [App\Http\Controllers\TeacherController::class, 'addStudentsToClass']);
    Route::post('/classes/{classId}/students/existing', [App\Http\Controllers\TeacherController::class, 'addExistingStudentsToClass']);
    Route::get('/classes/{classId}/students', [App\Http\Controllers\TeacherController::class, 'getClassStudents']);
    Route::delete('/classes/{classId}/students/{studentId}', [App\Http\Controllers\TeacherController::class, 'removeStudentFromClass']);
    
    // Attendance Management
    Route::post('/attendance/sessions', [App\Http\Controllers\TeacherController::class, 'startAttendanceSession']);
    Route::post('/attendance/quick-start', [App\Http\Controllers\TeacherController::class, 'quickStartSession']);
    Route::get('/attendance/sessions/{sessionId}', [App\Http\Controllers\TeacherController::class, 'getAttendanceSession']);
    Route::put('/attendance/sessions/{sessionId}/end', [App\Http\Controllers\TeacherController::class, 'endAttendanceSession']);
    Route::delete('/attendance/sessions/{sessionId}', [App\Http\Controllers\TeacherController::class, 'deleteAttendanceSession']);
    Route::get('/attendance/sessions/{sessionId}/export', [App\Http\Controllers\TeacherController::class, 'exportAttendanceSession']);
    Route::put('/attendance/sessions/{sessionId}/records', [App\Http\Controllers\TeacherController::class, 'updateAttendanceRecords']);
    Route::post('/attendance/mark', [App\Http\Controllers\TeacherController::class, 'markAttendance']);
    Route::post('/attendance/{sessionId}/mark', [App\Http\Controllers\TeacherController::class, 'markAttendance']);
    Route::post('/attendance/{sessionId}/qr-scan', [App\Http\Controllers\TeacherController::class, 'markAttendanceByQR']);
    Route::post('/attendance/{sessionId}/upload', [App\Http\Controllers\TeacherController::class, 'uploadAttendanceFile']);
    Route::get('/attendance/session/{sessionId}', [App\Http\Controllers\TeacherController::class, 'showAttendanceSession'])->name('attendance.session');
    Route::get('/attendance/direct/{classId}', [App\Http\Controllers\TeacherController::class, 'showDirectAttendance']);
    
    // File Management
    Route::post('/files/upload', [App\Http\Controllers\TeacherController::class, 'uploadFile']);
    Route::get('/files/analytics', [App\Http\Controllers\TeacherController::class, 'getFilesAnalytics']);
    Route::get('/files/all', [App\Http\Controllers\TeacherController::class, 'getAllFilesPage']);
    Route::get('/files/list', [App\Http\Controllers\TeacherController::class, 'getAllFiles']);
    Route::get('/files/recent', [App\Http\Controllers\TeacherController::class, 'getRecentFiles']);
    Route::get('/files/download/{fileId}', [App\Http\Controllers\TeacherController::class, 'downloadFile'])->name('files.download');
    Route::get('/files/class/{classId}', [App\Http\Controllers\TeacherController::class, 'getClassFiles']);
    
    // Reports
    Route::get('/reports/attendance', [App\Http\Controllers\TeacherController::class, 'attendanceReports']);
    Route::get('/reports/students', [App\Http\Controllers\TeacherController::class, 'studentReports']); 
    Route::get('/reports/export', [App\Http\Controllers\TeacherController::class, 'exportReports']);
    Route::get('/reports-data/attendance', [App\Http\Controllers\TeacherController::class, 'getAttendanceReports']);
    Route::post('/reports/export-data', [App\Http\Controllers\TeacherController::class, 'exportAttendanceReport']);
    Route::post('/reports/generate', [App\Http\Controllers\TeacherController::class, 'generateAttendanceReport']);

    // Excuse Request Management
    Route::post('/excuse-requests/{requestId}/approve', [App\Http\Controllers\TeacherController::class, 'approveExcuseRequest'])->name('excuse-requests.approve');
    Route::post('/excuse-requests/{requestId}/reject', [App\Http\Controllers\TeacherController::class, 'rejectExcuseRequest'])->name('excuse-requests.reject');
    Route::get('/excuse-requests/{requestId}/download-attachment', [App\Http\Controllers\TeacherController::class, 'downloadExcuseAttachment'])->name('excuse-requests.download-attachment');
});

// API routes for student lookup
Route::post('/api/students/lookup', [App\Http\Controllers\TeacherController::class, 'lookupStudent'])
    ->middleware(['auth', 'verified']);

// Student Routes
Route::middleware(['auth', 'verified'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/classes', [App\Http\Controllers\StudentController::class, 'classes'])->name('classes');
    Route::get('/attendance-history', [App\Http\Controllers\StudentController::class, 'attendanceHistory'])->name('attendance-history');
    Route::get('/excuse-requests', [App\Http\Controllers\StudentController::class, 'excuseRequests'])->name('excuse-requests');
    Route::post('/excuse-requests', [App\Http\Controllers\StudentController::class, 'submitExcuseRequest'])->name('excuse-requests.submit');
    Route::post('/quick-checkin', [App\Http\Controllers\StudentController::class, 'quickCheckIn'])->name('quick-checkin');
    Route::post('/self-checkin', [App\Http\Controllers\StudentController::class, 'selfCheckIn'])->name('self-checkin');
});

// Admin Routes (for non-teacher users)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/teachers', [App\Http\Controllers\AdminController::class, 'teachers'])->name('teachers');
    Route::post('/teachers', [App\Http\Controllers\AdminController::class, 'storeTeacher'])->name('teachers.store');
    Route::put('/teachers/{id}', [App\Http\Controllers\AdminController::class, 'updateTeacher'])->name('teachers.update');
    Route::patch('/teachers/{id}/toggle-status', [App\Http\Controllers\AdminController::class, 'toggleTeacherStatus'])->name('teachers.toggle-status');
    Route::delete('/teachers/{id}', [App\Http\Controllers\AdminController::class, 'deleteTeacher'])->name('teachers.delete');
    
    // Student Management
    Route::get('/students', [App\Http\Controllers\AdminController::class, 'students'])->name('students');
    Route::post('/students', [App\Http\Controllers\AdminController::class, 'storeStudent'])->name('students.store');
    Route::put('/students/{id}', [App\Http\Controllers\AdminController::class, 'updateStudent'])->name('students.update');
    Route::patch('/students/{id}/toggle-status', [App\Http\Controllers\AdminController::class, 'toggleStudentStatus'])->name('students.toggle-status');
    Route::delete('/students/{id}', [App\Http\Controllers\AdminController::class, 'deleteStudent'])->name('students.delete');
    
    // File Management Routes
    Route::get('/files', [\App\Http\Controllers\FilesController::class, 'index'])->name('files');
    Route::post('/files', [\App\Http\Controllers\FilesController::class, 'store'])->name('files.store');
    Route::get('/files/download/{filename}', [\App\Http\Controllers\FilesController::class, 'download'])->name('files.download');
    Route::delete('/files/{id}', [\App\Http\Controllers\FilesController::class, 'destroy'])->name('files.destroy');
    
    Route::get('/settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [App\Http\Controllers\AdminController::class, 'updateSettings'])->name('settings.update');
    Route::get('/reports', [App\Http\Controllers\AdminController::class, 'reports'])->name('reports');
    Route::post('/reports/export-pdf', [App\Http\Controllers\AdminController::class, 'exportReportPDF'])->name('reports.export-pdf');
    Route::post('/reports/email', [App\Http\Controllers\AdminController::class, 'emailReport'])->name('reports.email');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';