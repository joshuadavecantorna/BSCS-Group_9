<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
