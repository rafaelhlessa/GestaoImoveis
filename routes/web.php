<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\Auth\TokenLoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Middleware\ServiceProviderMiddleware;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Route::middleware(['auth', ServiceProviderMiddleware::class])->get('/redirect', function () {
    

// });
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('providers', ServiceProviderController::class);
});

// Route::get('/activate-account/{token}', [ActivationController::class, 'activate'])->middleware(['auth', 'verified'])->name('activate.account');
Route::get('/activate-account/{token}', [ActivationController::class, 'loginWithToken'])->middleware(['auth', 'verified'])->name('activate.account');
Route::get('/login/token/{token}', [TokenLoginController::class, 'loginWithToken'])->name('login.token');

Route::middleware(['auth'])->group(function () {
    Route::resource('authorizations', AuthorizationController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('property', PropertyController::class);
    Route::patch('property/property/{document}', [PropertyController::class, 'updateDocumentShow'])->name('property.updateDocument'); //Route PATCH to update the document show status
});    

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
