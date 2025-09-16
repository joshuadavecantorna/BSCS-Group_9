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

Route::get('files', [App\Http\Controllers\FilesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('files');

Route::post('files', [App\Http\Controllers\FilesController::class, 'store'])
    ->middleware(['auth', 'verified']);

Route::get('files/download/{filename}', [App\Http\Controllers\FilesController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('files.download');

Route::delete('files/{filename}', [App\Http\Controllers\FilesController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('files.destroy');

Route::post('files/{filename}/share', [App\Http\Controllers\FilesController::class, 'share'])
    ->middleware(['auth', 'verified'])
    ->name('files.share');

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
