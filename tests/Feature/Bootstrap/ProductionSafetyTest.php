<?php

use App\Domains\Catalog\Models\Service;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Leads\Models\Lead;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Faker\Generator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

// ── Admin seeding ────────────────────────────────────────────────────────

it('seeds the admin from the ADMIN_* settings', function () {
    config(['hive.admin.email' => 'me@studio.it', 'hive.admin.name' => 'Me', 'hive.admin.password' => 'correct horse battery']);

    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'me@studio.it')->sole();
    expect($admin->name)->toBe('Me');
    expect(Hash::check('correct horse battery', $admin->password))->toBeTrue();
});

it('never seeds the guessable default password outside local development', function () {
    config(['hive.admin.email' => 'me@studio.it', 'hive.admin.password' => null]);
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])
        ->expectsOutputToContain('Generated a password for me@studio.it')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'me@studio.it')->sole();
    expect(Hash::check('password', $admin->password))->toBeFalse();
});

it('does not reset an existing admin password unless one is configured', function () {
    User::factory()->create(['email' => 'me@studio.it', 'password' => 'my own password']);
    config(['hive.admin.email' => 'me@studio.it', 'hive.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(Hash::check('my own password', User::query()->where('email', 'me@studio.it')->sole()->password))->toBeTrue();
});

it('keeps the documented local-dev default password', function () {
    config(['hive.admin.email' => 'admin@hive.local', 'hive.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(Hash::check('password', User::query()->where('email', 'admin@hive.local')->sole()->password))->toBeTrue();
});

// ── Operations dashboards ────────────────────────────────────────────────

it('opens Horizon and Pulse to panel users, and only to them', function (string $ability) {
    expect(Gate::forUser(User::factory()->create())->allows($ability))->toBeTrue();
    expect(Gate::forUser(null)->allows($ability))->toBeFalse();
})->with(['viewHorizon', 'viewPulse']);

// ── Demo data ────────────────────────────────────────────────────────────

it('installs demo data idempotently and without dev-only dependencies', function () {
    // Production images run `composer install --no-dev`: Faker (and so
    // every model factory) is not available there.
    app()->bind(Generator::class, fn () => throw new RuntimeException('Faker is a dev-only dependency'));

    $counts = fn () => [
        Contact::count(), Website::count(), Lead::count(),
        FinancialEntry::count(), Fattura::count(), Service::count(),
    ];

    $this->seed(DemoDataSeeder::class);
    $afterFirstRun = $counts();
    $this->seed(DemoDataSeeder::class);

    expect($afterFirstRun[0])->toBeGreaterThan(0);
    expect($counts())->toBe($afterFirstRun);
});

// ── Configuration ────────────────────────────────────────────────────────

it('reads mail tuning from config, so it survives config:cache', function () {
    expect(config('hive.mail.ses_per_minute'))->toBe(10_000); // phpunit.xml
    expect(config('hive.mail.unsubscribe_ttl_days'))->toBe(365);
});
