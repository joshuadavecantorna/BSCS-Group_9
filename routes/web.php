<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('classes', ClassroomController::class)->names('classes');

    Route::prefix('classes/{class}')->group(function () {
        Route::post('students', [StudentController::class, 'store'])->name('classes.students.store');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('classes.students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('classes.students.destroy');
    });
});
