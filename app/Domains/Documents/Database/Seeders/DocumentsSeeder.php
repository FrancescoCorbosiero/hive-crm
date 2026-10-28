<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Seeders;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Two sample fatture for the demo workspace.
 *
 * Fattura numbers are fiscal artefacts, so this seeder never touches
 * the counter or upserts on (year, number) — doing so would overwrite a
 * real invoice and desync the sequence. Demo fatture are only added to
 * a ledger that has none yet (which also makes re-runs a no-op), and go
 * through FatturaService / PaymentsService like real ones.
 */
class DocumentsSeeder extends Seeder
{
    public function run(): void
    {
        if (Fattura::query()->exists()) {
            return;
        }

        $clientId = app(ContactsService::class)
            ->idsByEmails(['amministrazione@bellavistadolci.it'])
            ->first();

        if (! $clientId) {
            return;
        }

        $ownerId = User::query()->value('id');

        $samples = [
            [
                'lines' => [
                    ['description' => 'Manutenzione mensile sito', 'qty' => 1, 'unit_price_cents' => 8000, 'vat_rate' => 22],
                ],
                'paid' => true,
                'days_ago' => 30,
            ],
            [
                'lines' => [
                    ['description' => 'Restyling homepage', 'qty' => 1, 'unit_price_cents' => 220000, 'vat_rate' => 22],
                    ['description' => 'Migrazione contenuti', 'qty' => 4, 'unit_price_cents' => 7500, 'vat_rate' => 22],
                ],
                'paid' => false,
                'days_ago' => 5,
            ],
        ];

        foreach ($samples as $sample) {
            $issuedAt = now()->subDays($sample['days_ago']);

            $fattura = app(FatturaService::class)->create([
                'client_contact_id' => (int) $clientId,
                'issued_at' => $issuedAt,
                'due_date' => $issuedAt->copy()->addDays(30),
                'lines' => $sample['lines'],
                'owner_user_id' => $ownerId,
            ]);

            if ($sample['paid']) {
                app(PaymentsService::class)->record($fattura->id, [
                    'amount_cents' => $fattura->total_cents,
                    'paid_at' => $issuedAt->toDateString(),
                ]);
            }
        }
    }
}
