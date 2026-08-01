<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Upload
Route::get('/uploads/create', [UploadController::class, 'create'])->name('uploads.create');
Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/uploads/{upload}', [DashboardController::class, 'show'])->name('dashboard.show');
Route::get('/dashboard/logs', [DashboardController::class, 'logs'])->name('dashboard.logs');

Route::get('/dashboard/uploads/{upload}/status', [DashboardController::class, 'status'])->name('dashboard.status');
