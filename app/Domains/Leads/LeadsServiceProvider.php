<?php

declare(strict_types=1);

namespace App\Domains\Leads;

use App\Domains\Leads\Services\Public\LeadsService;
use Illuminate\Support\ServiceProvider;

class LeadsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LeadsService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
