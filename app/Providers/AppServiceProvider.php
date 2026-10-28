<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Pulse's default gate only opens the dashboard in `local`; like
        // Horizon (HorizonServiceProvider), it follows panel access.
        Gate::define('viewPulse', fn (?User $user = null): bool => $user?->canViewOperationsDashboards() ?? false);
    }
}
