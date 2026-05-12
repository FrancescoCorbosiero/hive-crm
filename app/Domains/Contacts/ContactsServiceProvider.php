<?php

declare(strict_types=1);

namespace App\Domains\Contacts;

use App\Domains\Contacts\Events\ContactCreated;
use App\Domains\Contacts\Listeners\RegisterContactCreatedActivity;
use App\Domains\Contacts\Listeners\TagCustomerOnFatturaPaid;
use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Documents\Events\FatturaPaid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Contacts domain service provider.
 *
 * Each domain owns its registration: migrations, factory namespace,
 * translations, event listeners. Filament resources are registered via
 * the matching ContactsPanelPlugin (added to AdminPanelProvider->plugins).
 */
class ContactsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContactsService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Translation files live at lang/{locale}/contacts/*.php and are
        // auto-discovered by Laravel's FileLoader; no namespace registration
        // is required. We deliberately keep them under the global lang/
        // tree so the locale switcher and Spatie translatable plugin can
        // see them.

        // Map Domain\Models\X → Domain\Database\Factories\XFactory so that
        // factory() autoresolution works for nested-domain models. Falls
        // back to Laravel's default Database\Factories\XFactory for
        // non-domain models like App\Models\User.
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if (str_starts_with($modelName, 'App\\Domains\\')) {
                $segments = explode('\\', $modelName);
                array_splice($segments, -2, 1, 'Database\\Factories');

                return implode('\\', $segments).'Factory';
            }

            $modelBasename = class_basename($modelName);

            return 'Database\\Factories\\'.$modelBasename.'Factory';
        });

        Event::listen(ContactCreated::class, RegisterContactCreatedActivity::class);
        Event::listen(FatturaPaid::class, TagCustomerOnFatturaPaid::class);
    }
}
