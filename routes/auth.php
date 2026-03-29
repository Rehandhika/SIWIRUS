<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LogoutController;

// Login Route (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/admin/masuk', \App\Livewire\Auth\LoginForm::class)->name('login');
});

// Authenticated Auth Routes
Route::middleware(['auth'])->group(function () {
    // POST logout
    Route::post('/keluar', [LogoutController::class, 'logout'])->name('logout');
    Route::post('/admin/keluar', [LogoutController::class, 'logout'])->name('admin.logout');
    
    // GET fallback - prevents 405 errors when accessed via GET (e.g., browser prefetch, extensions)
    // Properly invalidates session for security
    Route::get('/admin/keluar', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login')->with('error', 'Silakan gunakan tombol logout untuk keluar.');
    })->middleware('auth');
});
