<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleKmlCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === "OPTIONS") {
            return response('', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        $response = $next($request);

        // Add CORS headers to the response
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        
        // Add caching headers for better performance
        if ($request->is('property/document/*') || $request->is('property/kml/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}

// Para registrar o middleware, adicione ao app/Http/Kernel.php:

/*
protected $routeMiddleware = [
    // ... outros middlewares
    'kml.cors' => \App\Http\Middleware\HandleKmlCors::class,
];

// E nas rotas:
Route::middleware(['auth', 'kml.cors'])->group(function () {
    Route::get('/property/document/{id}', [PropertyController::class, 'viewDocument']);
    Route::get('/property/kml/{id}', [PropertyController::class, 'serveKml']);
});
*/