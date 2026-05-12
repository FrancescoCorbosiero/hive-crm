<?php

return [
    'page_title' => 'Demo data',
    'subtitle' => 'Install a small set of sample contacts, websites, leads, financial entries and documents to evaluate the app. Disabled by default in fresh installs.',

    'current_state' => [
        'heading' => 'Current workspace',
    ],

    'tables' => [
        'contacts' => 'Contacts',
        'websites' => 'Websites',
        'financial_entries' => 'Financial entries',
        'leads' => 'Leads',
        'documents' => 'Documents',
    ],

    'help' => [
        'idempotent' => 'Installing is idempotent — running it twice will not duplicate rows. Each demo record is upserted on a stable key (email, URL, etc).',
        'no_uninstall' => 'There is no uninstall. To wipe everything and start clean, run `php artisan migrate:fresh --seed` from the server.',
    ],

    'install' => [
        'action' => 'Install demo data',
        'confirm' => 'Install now',
        'modal_heading' => 'Install demo data?',
        'modal_description_empty' => 'Your workspace is empty. This will populate it with a small set of sample records across all domains.',
        'modal_description_non_empty' => 'Your workspace already contains data. Demo records will be upserted on stable keys; existing rows with matching keys will be updated, others remain untouched.',
        'success_title' => 'Demo data installed',
        'success_body' => 'Sample records are now available across the app.',
        'failure_title' => 'Installation failed',
    ],
];
