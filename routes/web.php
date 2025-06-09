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
use Illuminate\Support\Facades\Auth;

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
    
    // Rotas específicas para prestadores de serviço
    Route::prefix('service-provider')->name('service-provider.')->group(function () {
        Route::get('/', [ServiceProviderController::class, 'index'])->name('index');
        
        // API endpoints para AJAX (apenas para perfil misto - profile_id = 3)
        Route::get('/api/valuation-data', [ServiceProviderController::class, 'getValuationDataApi'])
            ->name('valuation-data');
        Route::get('/api/stats', [ServiceProviderController::class, 'getDashboardStatsApi'])
            ->name('stats');
            
        // Buscar propriedades de um cliente específico
        Route::get('/client/{clientId}/properties', [ServiceProviderController::class, 'getClientProperties'])
            ->name('client.properties');
    });
    
    // Rota para propriedades do cliente (mantendo compatibilidade)
    Route::get('/clients/{id}/property', [PropertyController::class, 'clientsProperty'])
        ->name('clients.property');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('authorizations', AuthorizationController::class);
    Route::patch('authorizations/authorizations/{auth}', [AuthorizationController::class, 'updateAuthChange'])->name('authorizations.updateAuthChange');
});

Route::get('/view-email/activate-account', function () {
    return view('emails.email');
})->name('view.email.activate-account');

// Rotas de propriedades principais
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('property', PropertyController::class);
    Route::get('propertyNew/{id}', [PropertyController::class, 'clientsProperty'])->name('clients.property');
    Route::get('propertyShow/{id?}', [PropertyController::class, 'clientShow'])->name('clients.show');
    
    // ROTAS CORRIGIDAS PARA DOCUMENTOS
    Route::get('property/document/{id}', [PropertyController::class, 'viewDocument'])
        ->name('property.getDocument'); // Nome que o frontend está usando
    
    // Rota específica para KML com CORS
    Route::get('property/kml/{id}', [PropertyController::class, 'serveKml'])
        ->name('property.kml.serve');
    
    Route::patch('property/property/{document}', [PropertyController::class, 'updateDocumentShow'])
        ->name('property.updateDocument');
});

// Handle OPTIONS requests for CORS (fora do middleware auth para preflight)
Route::options('property/document/{id}', function() {
    return response('', 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        'Access-Control-Max-Age' => '86400',
    ]);
});

Route::options('property/kml/{id}', function() {
    return response('', 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        'Access-Control-Max-Age' => '86400',
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route::middleware(['auth'])->group(function() {
//     Route::resource(
//         'properties.evaluations',
//         PropertyEvaluationController::class
//     )->shallow()
//       ->names([
//          'index'   => 'properties.evaluations.index',
//          'create'  => 'properties.evaluations.create',
//          'store'   => 'properties.evaluations.store',
//          'show'    => 'properties.evaluations.show',
//          'edit'    => 'properties.evaluations.edit',
//          'update'  => 'properties.evaluations.update',
//          'destroy' => 'properties.evaluations.destroy',
//       ]);
// });
// Route::middleware(['auth'])->group(function () {
//     // Rotas aninhadas para avaliações de propriedades
//     Route::prefix('properties/{property}')->name('properties.')->group(function () {
//         Route::resource('evaluations', PropertyEvaluationController::class)->names([
//             'index' => 'evaluations.index',
//             'create' => 'evaluations.create', 
//             'store' => 'evaluations.store',
//             'show' => 'evaluations.show',
//             'edit' => 'evaluations.edit',
//             'update' => 'evaluations.update',
//             'destroy' => 'evaluations.destroy'
//         ]);
//     });
// });
Route::middleware(['auth', 'verified'])->group(function () {

     Route::get('/properties/{property}/evaluations', [PropertyEvaluationController::class, 'index'])
        ->name('properties.evaluations.index')
        ->where('property', '[0-9]+');
    
    // Formulário de criação de avaliação
    Route::get('/properties/{property}/evaluations/create', [PropertyEvaluationController::class, 'create'])
        ->name('properties.evaluations.create')
        ->where('property', '[0-9]+');
    
    // Salvar nova avaliação
    Route::post('/properties/{property}/evaluations', [PropertyEvaluationController::class, 'store'])
        ->name('properties.evaluations.store')
        ->where('property', '[0-9]+');
    
    // Mostrar avaliação específica
    Route::get('/properties/{property}/evaluations/{evaluation}', [PropertyEvaluationController::class, 'show'])
        ->name('properties.evaluations.show')
        ->where(['property' => '[0-9]+', 'evaluation' => '[0-9]+']);
    
    // Formulário de edição de avaliação
    Route::get('/properties/{property}/evaluations/{evaluation}/edit', [PropertyEvaluationController::class, 'edit'])
        ->name('properties.evaluations.edit')
        ->where(['property' => '[0-9]+', 'evaluation' => '[0-9]+']);
    
    // Atualizar avaliação
    Route::put('/properties/{property}/evaluations/{evaluation}', [PropertyEvaluationController::class, 'update'])
        ->name('properties.evaluations.update')
        ->where(['property' => '[0-9]+', 'evaluation' => '[0-9]+']);
    
    // Deletar avaliação
    Route::delete('/properties/{property}/evaluations/{evaluation}', [PropertyEvaluationController::class, 'destroy'])
        ->name('properties.evaluations.destroy')
        ->where(['property' => '[0-9]+', 'evaluation' => '[0-9]+']);
    
    // Propriedades próprias - apenas perfis 1 e 3
    Route::get('/properties', [PropertyController::class, 'index'])
        ->name('property.index');
    
    Route::get('/property/create', [PropertyController::class, 'create'])
        ->name('property.create');
        
    Route::post('/property', [PropertyController::class, 'store'])
        ->name('property.store');
        
    // CORREÇÃO: Esta rota serve tanto para propriedades próprias quanto de clientes
    Route::get('/property/{property}', [PropertyController::class, 'show'])
        ->name('property.show');
        
    // CORREÇÃO: Criar um alias para a mesma rota
    Route::get('/clients/property/{property}', [PropertyController::class, 'show'])
        ->name('clients.show');
        
    Route::get('/property/{property}/edit', [PropertyController::class, 'edit'])
        ->name('property.edit');
        
    Route::put('/property/{property}', [PropertyController::class, 'update'])
        ->name('property.update');
        
    Route::delete('/property/{property}', [PropertyController::class, 'destroy'])
        ->name('property.destroy');
    
    // Propriedades de clientes - listagem
    Route::get('/clients/{id}/properties', [PropertyController::class, 'clientsProperty'])
        ->name('clients.property')
        ->where('id', '[0-9]+');
    
    // Documentos
    Route::get('/property/document/{id}', [PropertyController::class, 'viewDocument'])
        ->name('property.getDocument')
        ->where('id', '[0-9]+');
        
    Route::get('/property/kml/{id}', [PropertyController::class, 'serveKml'])
        ->name('property.getKml')
        ->where('id', '[0-9]+');
        
    Route::options('/property/kml/{id}', [PropertyController::class, 'handleKmlOptions']);
    
    // Atualizar visibilidade do documento
    Route::patch('/property/document/{documentId}', [PropertyController::class, 'updateDocument'])
        ->name('property.updateDocument')
        ->where('documentId', '[0-9]+');
        
    // Dashboard
    Route::get('/dashboard', [ServiceProviderController::class, 'index'])
        ->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/error/unauthorized', function () {
        return Inertia::render('Error/Unauthorized', [
            'message' => request('message', 'Acesso não autorizado.'),
            'type' => request('type', 'general'),
            'redirectTo' => request('redirect', '/dashboard'),
            'userProfile' => Auth::user() ? Auth::user()->profile_id : null,
        ]);
    })->name('error.unauthorized');
});

Route::get('/debug-create-data', function() {
    $currentUser = Auth::user();
    $controller = new \App\Http\Controllers\PropertyController();
    
    $reflection = new ReflectionClass($controller);
    $getUsersMethod = $reflection->getMethod('getAvailableUsers');
    $getUsersMethod->setAccessible(true);
    $getAuthMethod = $reflection->getMethod('getUserAuthorizations');
    $getAuthMethod->setAccessible(true);
    
    $users = $getUsersMethod->invoke($controller, $currentUser);
    $authorizations = $getAuthMethod->invoke($controller, $currentUser);
    
    return response()->json([
        'current_user' => $currentUser,
        'users' => $users,
        'authorizations' => $authorizations,
        'users_count' => count($users),
        'authorizations_count' => count($authorizations),
    ]);
});

// Rota de debug (remover em produção)
    Route::get('/debug/property-evaluations/tables', [PropertyEvaluationController::class, 'debugTables'])
        ->name('debug.property-evaluations.tables');


Route::middleware(['auth','can:isAdmin'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
         Route::get('/devs',   [DevController::class, 'index'])->name('dev.index');
         Route::post('/dev',  [DevController::class, 'store'])->name('dev.store');
     });

require __DIR__.'/auth.php';