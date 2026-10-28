<?php

namespace Database\Seeders;

use App\Domains\Catalog\Database\Seeders\CatalogSeeder;
use App\Domains\Contacts\Database\Seeders\ContactsSeeder;
use App\Domains\Documents\Database\Seeders\DocumentsSeeder;
use App\Domains\Finance\Database\Seeders\FinancialEntriesSeeder;
use App\Domains\Leads\Database\Seeders\LeadsSeeder;
use App\Domains\Websites\Database\Seeders\WebsitesSeeder;
use Illuminate\Database\Seeder;

/**
 * Opt-in demo data. Populates each domain with a small, realistic
 * sample so widgets/tables aren't empty for evaluation.
 *
 * Triggered manually from the admin UI (Settings → Demo data) — NEVER
 * called from DatabaseSeeder, so production first-boot stays clean.
 *
 * Idempotent: every domain seeder uses updateOrCreate keyed on stable
 * fields, so running this twice does not multiply the data.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContactsSeeder::class,
            WebsitesSeeder::class,
            FinancialEntriesSeeder::class,
            LeadsSeeder::class,
            CatalogSeeder::class,
            DocumentsSeeder::class,
        ]);
    }
}
