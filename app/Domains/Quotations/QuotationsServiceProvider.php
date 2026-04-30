<?php

declare(strict_types=1);

namespace App\Domains\Quotations;

use App\Domains\Quotations\Services\Internal\QuotationPdfRenderer;
use App\Domains\Quotations\Services\Public\QuotationsService;
use Illuminate\Support\ServiceProvider;

class QuotationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuotationPdfRenderer::class);
        $this->app->singleton(QuotationsService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
