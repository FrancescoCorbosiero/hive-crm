<?php

declare(strict_types=1);

namespace App\Domains\Leads\Database\Seeders;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use Illuminate\Database\Seeder;

class LeadsSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = \App\Models\User::query()->value('id');

        $leads = [
            [
                'name' => 'Trattoria Da Nonna Pina',
                'email' => 'info@danonnapina.it',
                'phone' => '+39 02 5555 1234',
                'source' => LeadSource::Referral,
                'status' => LeadStatus::Qualified,
                'estimated_value_cents' => 250_000,
                'notes' => 'Vogliono un sito vetrina con sistema di prenotazione e menu del giorno.',
                'next_action_at' => now()->addDays(2),
            ],
            [
                'name' => 'Cooperativa Agricola Verdi Pascoli',
                'email' => 'amministrazione@verdipascoli.coop',
                'phone' => '+39 045 666 9988',
                'source' => LeadSource::Inbound,
                'status' => LeadStatus::Proposal,
                'estimated_value_cents' => 480_000,
                'notes' => 'Proposta inviata: e-commerce B2B per ristoranti + integrazione gestionale.',
                'next_action_at' => now()->addDays(5),
            ],
            [
                'name' => 'Andrea Conti',
                'email' => 'andrea.conti@gmail.com',
                'phone' => '+39 348 222 7766',
                'source' => LeadSource::Website,
                'status' => LeadStatus::Contacted,
                'estimated_value_cents' => 120_000,
                'notes' => 'Personal branding di un consulente finanziario.',
                'next_action_at' => now()->addDays(1),
            ],
            [
                'name' => 'Palestra FitMilano',
                'email' => 'manager@fitmilano.it',
                'phone' => '+39 02 8877 4433',
                'source' => LeadSource::Event,
                'status' => LeadStatus::New,
                'estimated_value_cents' => 90_000,
                'notes' => 'Conosciuti al meetup WordPress Milano.',
                'next_action_at' => now()->addDays(3),
            ],
            [
                'name' => 'Studio Architetti Bianchi',
                'email' => 'info@studiobianchi.archi',
                'phone' => '+39 06 9988 7766',
                'source' => LeadSource::ColdOutreach,
                'status' => LeadStatus::Lost,
                'estimated_value_cents' => 200_000,
                'notes' => 'Hanno scelto un\'altra agenzia per ragioni di budget.',
                'next_action_at' => null,
            ],
        ];

        foreach ($leads as $row) {
            Lead::query()->updateOrCreate(
                ['email' => $row['email']],
                array_merge($row, [
                    'source' => $row['source']->value,
                    'status' => $row['status']->value,
                    'estimated_value_currency' => 'EUR',
                    'owner_user_id' => $ownerId,
                ]),
            );
        }
    }
}
