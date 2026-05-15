<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,

    // ── Domains ────────────────────────────────────────────────────────
    App\Domains\Contacts\ContactsServiceProvider::class,
    App\Domains\Websites\WebsitesServiceProvider::class,
    App\Domains\DomainNames\DomainNamesServiceProvider::class,
    App\Domains\Repositories\RepositoriesServiceProvider::class,
    App\Domains\Catalog\CatalogServiceProvider::class,
    App\Domains\Finance\FinanceServiceProvider::class,
    App\Domains\Leads\LeadsServiceProvider::class,
    App\Domains\Calendar\CalendarServiceProvider::class,
    App\Domains\Mail\MailServiceProvider::class,
    App\Domains\Documents\DocumentsServiceProvider::class,
    App\Domains\Quotations\QuotationsServiceProvider::class,
    App\Domains\Scheduling\SchedulingServiceProvider::class,
    App\Domains\Settings\SettingsServiceProvider::class,
];
