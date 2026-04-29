<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,

    // ── Domains ────────────────────────────────────────────────────────
    App\Domains\Contacts\ContactsServiceProvider::class,
    App\Domains\Websites\WebsitesServiceProvider::class,
    App\Domains\Finance\FinanceServiceProvider::class,
];
