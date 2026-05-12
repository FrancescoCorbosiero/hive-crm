<?php

declare(strict_types=1);

namespace App\Domains\Documents;

use App\Domains\Documents\Console\Commands\IssueRecurringFattureCommand;
use App\Domains\Documents\Console\Commands\RecomputeOverdueFattureCommand;
use App\Domains\Documents\Services\Internal\FatturaPdfRenderer;
use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentsService::class);
        $this->app->singleton(FatturaPdfRenderer::class);
        $this->app->singleton(FatturaService::class);
        $this->app->singleton(PaymentsService::class);
        $this->app->singleton(RecurringFatturaService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                IssueRecurringFattureCommand::class,
                RecomputeOverdueFattureCommand::class,
            ]);
        }
    }
}
