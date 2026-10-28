<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production-safe default seeder. Creates ONLY the admin user.
 *
 * Demo data (sample leads, contacts, websites, transactions, documents)
 * lives in DemoDataSeeder and is installable on-demand from the admin UI
 * (Settings → Demo data). This prevents fake records from landing in a
 * real production workspace on first boot.
 *
 * The admin comes from config('hive.admin') (ADMIN_EMAIL / ADMIN_NAME /
 * ADMIN_PASSWORD). An existing admin keeps their password unless
 * ADMIN_PASSWORD is set; a new one never gets the guessable local-dev
 * default outside local development.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('hive.admin.email');
        $password = (string) config('hive.admin.password');

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->name = (string) config('hive.admin.name');
        $admin->email_verified_at ??= now();

        if ($password !== '') {
            $admin->password = $password; // hashed by the model cast
        } elseif (! $admin->exists) {
            $admin->password = $this->initialPassword($email);
        }

        $admin->save();
    }

    private function initialPassword(string $email): string
    {
        if (app()->environment('local', 'testing')) {
            return 'password';
        }

        $generated = Str::password(24);

        $this->command?->warn("No ADMIN_PASSWORD set. Generated a password for {$email}: {$generated}");
        $this->command?->warn('Store it now — it is not shown again. To change it, set ADMIN_PASSWORD and re-run db:seed.');

        return $generated;
    }
}
