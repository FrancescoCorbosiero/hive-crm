<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Seeders;

use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use Illuminate\Database\Seeder;

class DocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = \App\Models\User::query()->value('id');
        $year = (int) now()->year;

        $clientId = app(\App\Domains\Contacts\Services\Public\ContactsService::class)
            ->idsByEmails(['amministrazione@bellavistadolci.it'])
            ->first();

        if (! $clientId) {
            return;
        }

        FatturaCounter::query()->updateOrCreate(['year' => $year], ['last_number' => 0]);

        $samples = [
            [
                'lines' => [
                    ['description' => 'Manutenzione mensile sito', 'qty' => 1, 'unit_price_cents' => 8000, 'vat_rate' => 22],
                ],
                'payment_status' => PaymentStatus::Paid,
                'days_ago' => 30,
            ],
            [
                'lines' => [
                    ['description' => 'Restyling homepage', 'qty' => 1, 'unit_price_cents' => 220000, 'vat_rate' => 22],
                    ['description' => 'Migrazione contenuti', 'qty' => 4, 'unit_price_cents' => 7500, 'vat_rate' => 22],
                ],
                'payment_status' => PaymentStatus::Unpaid,
                'days_ago' => 5,
            ],
        ];

        foreach ($samples as $sample) {
            $subtotal = 0;
            $vat = 0;
            foreach ($sample['lines'] as $line) {
                $sub = (int) round($line['qty'] * $line['unit_price_cents']);
                $subtotal += $sub;
                $vat += (int) round($sub * $line['vat_rate'] / 100);
            }
            $total = $subtotal + $vat;

            $counter = FatturaCounter::query()->lockForUpdate()->find($year);
            $counter->last_number = $counter->last_number + 1;
            $counter->save();

            Fattura::query()->updateOrCreate(
                ['year' => $year, 'number' => $counter->last_number],
                [
                    'client_contact_id' => $clientId,
                    'issued_at' => now()->subDays($sample['days_ago']),
                    'lines' => $sample['lines'],
                    'subtotal_cents' => $subtotal,
                    'vat_cents' => $vat,
                    'total_cents' => $total,
                    'currency' => 'EUR',
                    'payment_status' => $sample['payment_status']->value,
                    'owner_user_id' => $ownerId,
                ],
            );
        }
    }
}
