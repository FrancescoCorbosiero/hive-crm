<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Production-safe default seeder. Creates ONLY the admin user.
 *
 * Demo data (sample leads, contacts, websites, transactions, documents)
 * lives in DemoDataSeeder and is installable on-demand from the admin UI
 * (Settings → Demo data). This prevents fake records from landing in a
 * real production workspace on first boot.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('hive.admin.email', env('ADMIN_EMAIL', 'admin@hive.local'));
        $name = config('hive.admin.name', env('ADMIN_NAME', 'Hive Admin'));
        $password = env('ADMIN_PASSWORD', 'password');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
