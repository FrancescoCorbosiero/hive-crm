<?php

declare(strict_types=1);

namespace App\Domains\Repositories;

use App\Domains\Repositories\Services\Public\RepositoriesService;
use Illuminate\Support\ServiceProvider;

class RepositoriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RepositoriesService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
