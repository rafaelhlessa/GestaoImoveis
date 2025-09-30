<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ServiceProviderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Redireciona se for proprietário puro
        if ($user->hasProfile('proprietario') && !$user->hasProfile('prestador')) {
            return redirect()->route('service-providers.index');
        }
        // Redireciona para dashboard padrão
        return redirect()->route('dashboard');
    }
}
