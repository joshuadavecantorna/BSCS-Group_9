<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('files/{filename}', [App\Http\Controllers\FilesController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('files.view');

Route::delete('files/{filename}', [App\Http\Controllers\FilesController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('files.destroy');

Route::post('files/{filename}/share', [App\Http\Controllers\FilesController::class, 'share'])
    ->middleware(['auth', 'verified'])
    ->name('files.share');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
