<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Database\Seeders;

use App\Domains\Catalog\Enums\ServiceCategory;
use App\Domains\Catalog\Models\Service;
use App\Domains\Quotations\Enums\LineCadence;
use Illuminate\Database\Seeder;

/**
 * A small, realistic starter catalog for a web-design shop. Idempotent:
 * updateOrCreate keyed on `name`, so re-running never multiplies rows.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = \App\Models\User::query()->value('id');

        $services = [
            [
                'name' => 'WordPress — Base',
                'category' => ServiceCategory::Websites,
                'description' => 'Sito vetrina WordPress fino a 5 pagine, tema personalizzato, modulo contatti.',
                'default_unit_price_cents' => 150_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 10,
            ],
            [
                'name' => 'WordPress — Pro',
                'category' => ServiceCategory::Websites,
                'description' => 'Sito WordPress completo: pagine illimitate, blog, multilingua, ottimizzazione base.',
                'default_unit_price_cents' => 350_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 20,
            ],
            [
                'name' => 'Web app su misura',
                'category' => ServiceCategory::Software,
                'description' => 'Sviluppo di applicazione web custom — stima a corpo, da definire in analisi.',
                'default_unit_price_cents' => 800_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 30,
            ],
            [
                'name' => 'Audit SEO',
                'category' => ServiceCategory::Seo,
                'description' => 'Analisi SEO tecnica e di contenuto con report e piano di interventi.',
                'default_unit_price_cents' => 60_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 40,
            ],
            [
                'name' => 'Retainer SEO mensile',
                'category' => ServiceCategory::Seo,
                'description' => 'Attività SEO continuativa: monitoraggio, contenuti, link building, report mensile.',
                'default_unit_price_cents' => 40_000,
                'default_cadence' => LineCadence::Monthly,
                'sort_order' => 50,
            ],
            [
                'name' => 'Brand identity',
                'category' => ServiceCategory::BrandingMarketing,
                'description' => 'Logo, palette, tipografia e linee guida di base in un brand book sintetico.',
                'default_unit_price_cents' => 120_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 60,
            ],
            [
                'name' => 'Hosting all-inclusive',
                'category' => ServiceCategory::HostingDomains,
                'description' => 'Hosting gestito, backup giornalieri, certificato SSL e aggiornamenti.',
                'default_unit_price_cents' => 15_000,
                'default_cadence' => LineCadence::Yearly,
                'sort_order' => 70,
            ],
            [
                'name' => 'Ora di consulenza',
                'category' => ServiceCategory::Consulting,
                'description' => 'Consulenza tecnica o strategica fatturata a ore.',
                'default_unit_price_cents' => 8_000,
                'default_cadence' => LineCadence::UnaTantum,
                'sort_order' => 80,
            ],
        ];

        foreach ($services as $row) {
            Service::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'category' => $row['category']->value,
                    'description' => $row['description'],
                    'default_unit_price_cents' => $row['default_unit_price_cents'],
                    'currency' => 'EUR',
                    'default_vat_rate' => 22,
                    'default_cadence' => $row['default_cadence']->value,
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'owner_user_id' => $ownerId,
                ],
            );
        }
    }
}
