<?php

declare(strict_types=1);

namespace App\Domains\Finance\Database\Seeders;

use App\Domains\Finance\Enums\TransactionSource;
use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Domains\Websites\Models\Website;
use Illuminate\Database\Seeder;

class TransactionsSeeder extends Seeder
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

                Transaction::query()->updateOrCreate(
                    [
                        'source_type' => TransactionSource::Website->value,
                        'source_id' => $website->id,
                        'occurred_at' => $date,
                        'category' => 'website_subscription',
                    ],
                    [
                        'type' => TransactionType::Income->value,
                        'amount_cents' => $cents,
                        'currency' => 'EUR',
                        'description' => 'Abbonamento mensile — '.$host,
                        'contact_id' => $website->owner_contact_id,
                        'owner_user_id' => $ownerId,
                    ],
                );
            }
        }

        // Realistic recurring expenses spread across the year.
        $expenses = [
            ['Hosting Contabo VPS', 'hosting', 1499, 1],
            ['Object Storage Contabo', 'hosting', 350, 1],
            ['Aruba domini', 'hosting', 1290, 6],
            ['Filament licenze', 'software', 19900, 12],
            ['Adobe Creative Cloud', 'software', 6099, 1],
            ['Commercialista', 'taxes', 12000, 3],
        ];

        foreach ($expenses as [$desc, $category, $cents, $everyMonths]) {
            for ($m = 0; $m < 12; $m += $everyMonths) {
                $date = $today->copy()->subMonths($m)->day(20);

                Transaction::query()->updateOrCreate(
                    [
                        'description' => $desc,
                        'occurred_at' => $date,
                    ],
                    [
                        'type' => TransactionType::Expense->value,
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

            Transaction::query()->updateOrCreate(
                ['description' => $desc, 'occurred_at' => $date],
                [
                    'type' => TransactionType::Income->value,
                    'amount_cents' => $cents,
                    'currency' => 'EUR',
                    'category' => $category,
                    'owner_user_id' => $ownerId,
                ],
            );
        }
    }
}
