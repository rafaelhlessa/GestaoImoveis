<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\Auth\TokenLoginController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Middleware\ServiceProviderMiddleware;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 1️⃣ Registro
// Route::post('/register', [RegisteredUserController::class, 'store'])
//      ->name('register');

// 2️⃣ Ativação
Route::get('/activate/{token}', [ActivationController::class, 'activate'])
     ->name('activation.activate');

// // Exibe o formulário para solicitar o link de redefinição de senha
// Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
//     ->middleware('guest')
//     ->name('password.request');

// // Processa o formulário e envia o email com o link de redefinição
// Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
//     ->middleware('guest')
//     ->name('password.email');

// // Exibe o formulário de redefinição de senha
// Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showResetForm'])
//     ->middleware('guest')
//     ->name('password.reset');

// // Processa o formulário de redefinição de senha
// Route::put('/reset-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'reset'])
//     ->middleware('guest')
//     ->name('password.update');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest', 'throttle:login,10,1');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [ServiceProviderController::class, 'index'])->name('dashboard');
});

// Route::get('/activate-account/{token}', [ActivationController::class, 'activate'])->middleware(['auth', 'verified'])->name('activate.account');
// Route::get('/activate-account/{token}', [ActivationController::class, 'loginWithToken'])->middleware(['auth', 'verified'])->name('activate.account');
// Route::get('/login/token/{token}', [TokenLoginController::class, 'loginWithToken'])->name('login.token');

Route::middleware(['auth'])->group(function () {
    Route::resource('authorizations', AuthorizationController::class);
    Route::patch('authorizations/authorizations/{auth}', [AuthorizationController::class, 'updateAuthChange'])->name('authorizations.updateAuthChange'); //Route PATCH to update the authorization change status
});

Route::get('/view-email/activate-account', function () {
    return view('emails.email');
})->name('view.email.activate-account');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('property', PropertyController::class);
    Route::get('propertyNew/{id?}', [PropertyController::class, 'clientsProperty'])->name('clients.property');
    Route::get('propertyShow/{id?}', [PropertyController::class, 'clientShow'])->name('clients.show');
    Route::get('property/document/kml/{id}', [PropertyController::class, 'getKmlDocument'])->name('property.getKmlDocument');
    Route::get('property/document/{id}', [PropertyController::class, 'getDocument'])->name('property.getDocument');
    Route::patch('property/property/{document}', [PropertyController::class, 'updateDocumentShow'])->name('property.updateDocument'); //Route PATCH to update the document show status
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__.'/auth.php';
