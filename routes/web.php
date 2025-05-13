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
use App\Http\Controllers\DevController;
use App\Http\Controllers\PropertyEvaluationController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Ativação
Route::get('/activate/{token}', [ActivationController::class, 'activate'])
     ->name('activation.activate');

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

Route::middleware(['auth'])->group(function() {
    Route::resource(
        'properties.evaluations',
        PropertyEvaluationController::class
    )->shallow()
      ->names([
         'index'   => 'properties.evaluations.index',
         'create'  => 'properties.evaluations.create',
         'store'   => 'properties.evaluations.store',
         'show'    => 'properties.evaluations.show',
         'edit'    => 'properties.evaluations.edit',
         'update'  => 'properties.evaluations.update',
         'destroy' => 'properties.evaluations.destroy',
      ]);
});      

Route::middleware(['auth','can:isAdmin'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
         Route::get('/devs',   [DevController::class, 'index'])->name('dev.index');
         Route::post('/dev',  [DevController::class, 'store'])->name('dev.store');
     });


require __DIR__.'/auth.php';
