<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\Auth\TokenLoginController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Route::get('/activate-account/{token}', [ActivationController::class, 'activate'])->middleware(['auth', 'verified'])->name('activate.account');
Route::get('/activate-account/{token}', [ActivationController::class, 'loginWithToken'])->middleware(['auth', 'verified'])->name('activate.account');
Route::get('/login/token/{token}', [TokenLoginController::class, 'loginWithToken'])->name('login.token');

Route::get('/regist', function () {
        return Inertia::render('RegisterStep1');
    })->name('regist');

Route::middleware(['auth', 'can:active-user'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
