<?php

declare(strict_types=1);

namespace App\Domains\Finance\Database\Seeders;

use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Websites\Models\Website;
use Illuminate\Database\Seeder;

class FinancialEntriesSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = \App\Models\User::query()->value('id');

        // Per-website monthly subscription income for the last 12 months.
        $websites = Website::query()->whereNotNull('owner_contact_id')->get();
        $unitMonthly = [
            'bellavistadolci.it' => 8000,
            'rossiebianchi.legal' => 12000,
            'marcobertolini.photo' => 4500,
            'romanodesign.it' => 9500,
        ];

        $today = now()->startOfMonth();

        foreach ($websites as $website) {
            $host = parse_url($website->url, PHP_URL_HOST) ?? '';
            $cents = $unitMonthly[$host] ?? 6500;

            for ($m = 0; $m < 12; $m++) {
                $date = $today->copy()->subMonths($m)->day(5);

                FinancialEntry::query()->updateOrCreate(
                    [
                        'source_type' => FinancialEntrySource::Website->value,
                        'source_id' => $website->id,
                        'occurred_at' => $date,
                        'category' => 'website_subscription',
                    ],
                    [
                        'type' => FinancialEntryType::Income->value,
                        'amount_cents' => $cents,
                        'currency' => 'EUR',
                        'description' => 'Abbonamento mensile — '.$host,
                        'contact_id' => $website->owner_contact_id,
                        'owner_user_id' => $ownerId,
                    ],
                );
            }
        }

        // Realistic recurring losses spread across the year.
        $losses = [
            ['Hosting Contabo VPS', 'hosting', 1499, 1],
            ['Object Storage Contabo', 'hosting', 350, 1],
            ['Aruba domini', 'hosting', 1290, 6],
            ['Filament licenze', 'software', 19900, 12],
            ['Adobe Creative Cloud', 'software', 6099, 1],
            ['Commercialista', 'taxes', 12000, 3],
        ];

        foreach ($losses as [$desc, $category, $cents, $everyMonths]) {
            for ($m = 0; $m < 12; $m += $everyMonths) {
                $date = $today->copy()->subMonths($m)->day(20);

                FinancialEntry::query()->updateOrCreate(
                    [
                        'description' => $desc,
                        'occurred_at' => $date,
                    ],
                    [
                        'type' => FinancialEntryType::Loss->value,
                        'amount_cents' => $cents,
                        'currency' => 'EUR',
                        'category' => $category,
                        'owner_user_id' => $ownerId,
                    ],
                );
            }
        }

        // A handful of one-off project incomes for variety.
        $oneOffs = [
            ['Restyling sito Pasticceria Bellavista', 'one_time_project', 220000, 4],
            ['Audit prestazioni Studio Legale', 'consulting', 95000, 2],
            ['Setup tracking analytics Romano Design', 'consulting', 65000, 7],
        ];

        foreach ($oneOffs as [$desc, $category, $cents, $monthsAgo]) {
            $date = $today->copy()->subMonths($monthsAgo)->day(15);

            FinancialEntry::query()->updateOrCreate(
                ['description' => $desc, 'occurred_at' => $date],
                [
                    'type' => FinancialEntryType::Income->value,
                    'amount_cents' => $cents,
                    'currency' => 'EUR',
                    'category' => $category,
                    'owner_user_id' => $ownerId,
                ],
            );
        }
    }
}
