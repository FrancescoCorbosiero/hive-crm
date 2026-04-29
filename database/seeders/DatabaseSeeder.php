<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        $this->call([
            \App\Domains\Contacts\Database\Seeders\ContactsSeeder::class,
            \App\Domains\Websites\Database\Seeders\WebsitesSeeder::class,
            \App\Domains\Finance\Database\Seeders\TransactionsSeeder::class,
        ]);
    }
}
