<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use App\Http\Controllers\EvaluationCriterionController;
use App\Http\Controllers\PropertyEvaluationController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionController;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


// 2️⃣ Ativação
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

// Critérios (admin)
Route::middleware(['auth'])->group(function() {
    Route::resource('criteria', EvaluationCriterionController::class)
         ->names([
             'index'   => 'criteria.index',
             'create'  => 'criteria.create',
             'store'   => 'criteria.store',
             'edit'    => 'criteria.edit',
             'update'  => 'criteria.update',
             'destroy' => 'criteria.destroy',
         ]);

    // Planos (admin)
    Route::prefix('plans/admin')->name('plans.admin.')->group(function() {
        Route::get('/', [SubscriptionPlanController::class, 'index'])
             ->name('index');
        Route::get('/create', [SubscriptionPlanController::class, 'create'])
             ->name('create');
        Route::post('/', [SubscriptionPlanController::class, 'store'])
             ->name('store');
        Route::get('/{plan}/edit', [SubscriptionPlanController::class, 'edit'])
             ->name('edit');
        Route::put('/{plan}', [SubscriptionPlanController::class, 'update'])
             ->name('update');
        Route::delete('/{plan}', [SubscriptionPlanController::class, 'destroy'])
             ->name('destroy');
    });

    // Avaliações
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

    // Assinaturas (usuário)
    Route::get('/plans', [SubscriptionController::class, 'showPlans'])
         ->name('subscriptions.plans');
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])
         ->name('subscriptions.subscribe');
    Route::post('/subscription/webhook', [SubscriptionController::class, 'webhook'])
         ->name('subscriptions.webhook');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
         ->name('subscriptions.cancel');
});



Route::middleware(['auth','can:isAdmin'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
         Route::get('dev',   [DevController::class, 'index'])->name('dev.index');
         Route::post('dev',  [DevController::class, 'store'])->name('dev.store');
     });

require __DIR__.'/auth.php';
