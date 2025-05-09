<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Property;
use App\Policies\PropertyPolicy;

class AppServiceProvider extends ServiceProvider
{
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
        Gate::define('isAdmin', fn (User $user) => (bool) $user->is_admin);

        Vite::prefetch(concurrency: 3);

        Gate::define('active-user', function (User $user) {
            return $user->is_active === 1;
        });
    }
}
