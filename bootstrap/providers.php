<?php

use App\Domains\Calendar\CalendarServiceProvider;
use App\Domains\Catalog\CatalogServiceProvider;
use App\Domains\Contacts\ContactsServiceProvider;
use App\Domains\Documents\DocumentsServiceProvider;
use App\Domains\DomainNames\DomainNamesServiceProvider;
use App\Domains\Finance\FinanceServiceProvider;
use App\Domains\Leads\LeadsServiceProvider;
use App\Domains\Mail\MailServiceProvider;
use App\Domains\Quotations\QuotationsServiceProvider;
use App\Domains\Repositories\RepositoriesServiceProvider;
use App\Domains\Scheduling\SchedulingServiceProvider;
use App\Domains\Settings\SettingsServiceProvider;
use App\Domains\Websites\WebsitesServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,

    // ── Domains ────────────────────────────────────────────────────────
    ContactsServiceProvider::class,
    WebsitesServiceProvider::class,
    DomainNamesServiceProvider::class,
    RepositoriesServiceProvider::class,
    CatalogServiceProvider::class,
    FinanceServiceProvider::class,
    LeadsServiceProvider::class,
    CalendarServiceProvider::class,
    MailServiceProvider::class,
    DocumentsServiceProvider::class,
    QuotationsServiceProvider::class,
    SchedulingServiceProvider::class,
    SettingsServiceProvider::class,
];
