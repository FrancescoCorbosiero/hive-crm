<?php

declare(strict_types=1);

namespace App\Domains\Documents;

use App\Domains\Documents\Services\Internal\FatturaPdfRenderer;
use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Documents\Services\Public\FatturaService;
use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentsService::class);
        $this->app->singleton(FatturaPdfRenderer::class);
        $this->app->singleton(FatturaService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
