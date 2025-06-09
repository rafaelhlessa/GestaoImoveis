<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Property;
use App\Policies\PropertyPolicy;
use App\Models\PropertyDocument;
use App\Models\PropertyEvaluation;
use App\Models\Activity;
use App\Policies\PropertyDocumentPolicy;
use App\Policies\PropertyEvaluationPolicy;
use App\Policies\ActivityPolicy;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        Property::class => PropertyPolicy::class,
        PropertyDocument::class => PropertyDocumentPolicy::class,
        PropertyEvaluation::class => PropertyEvaluationPolicy::class,
        Activity::class => ActivityPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policies should be registered in AuthServiceProvider, not here
        // If you want to register policies here, use Gate::policy() instead

        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(PropertyEvaluation::class, PropertyEvaluationPolicy::class);
        Gate::define('isAdmin', fn (User $user) => (bool) $user->is_admin);

        Vite::prefetch(concurrency: 3);

        Gate::define('active-user', function (User $user) {
            return $user->is_active === 1;
        });
        Gate::before(function ($user, $ability) {
            Log::debug('Gate::before', [
                'user_id' => $user->id,
                'ability' => $ability,
                'is_active' => $user->is_active,
            ]);
            
            
            // Não retorne nada aqui para permitir que as verificações normais ocorram
        });
    }
    
}
