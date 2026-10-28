<?php

/*
|--------------------------------------------------------------------------
| Hive application settings
|--------------------------------------------------------------------------
|
| App-specific knobs. Runtime code reads these through config() — never
| env() — so they keep working once `php artisan config:cache` (part of
| `optimize`, which the deploy runbook runs) has stopped .env loading.
|
*/

return [

    // First admin user, created by `php artisan db:seed` (DatabaseSeeder).
    // ADMIN_PASSWORD is required outside local development: without it the
    // seeder generates a random password and prints it once. Setting it
    // and re-running db:seed is also how you reset the admin password.
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@hive.local'),
        'name' => env('ADMIN_NAME', 'Hive Admin'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'mail' => [
        // Campaign sends per minute through SES. Sandbox accounts allow
        // ~1/s; raise once production access is granted (14/s default).
        'ses_per_minute' => (int) env('MAIL_SES_PER_MINUTE', 60),

        // How long an unsubscribe link in a campaign email stays valid.
        'unsubscribe_ttl_days' => (int) env('APP_UNSUBSCRIBE_TTL_DAYS', 365),
    ],

];
