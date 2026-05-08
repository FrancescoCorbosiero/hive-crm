<?php

declare(strict_types=1);

namespace App\Domains\Leads\Database\Seeders;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Enums\LostReason;
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
                'company_name' => 'Trattoria Da Nonna Pina S.r.l.',
                'email' => 'info@danonnapina.it',
                'phone' => '+39 02 5555 1234',
                'source' => LeadSource::Referral,
                'status' => LeadStatus::Qualified,
                'estimated_value_cents' => 250_000,
                'notes' => 'Vogliono un sito vetrina con sistema di prenotazione e menu del giorno.',
                'next_action_at' => now()->addDays(2),
                'last_contacted_at' => now()->subDays(3),
                'lost_reason' => null,
            ],
            [
                'name' => 'Cooperativa Agricola Verdi Pascoli',
                'company_name' => 'Verdi Pascoli Soc. Coop.',
                'email' => 'amministrazione@verdipascoli.coop',
                'phone' => '+39 045 666 9988',
                'source' => LeadSource::Inbound,
                'status' => LeadStatus::Proposal,
                'estimated_value_cents' => 480_000,
                'notes' => 'Proposta inviata: e-commerce B2B per ristoranti + integrazione gestionale.',
                'next_action_at' => now()->addDays(5),
                'last_contacted_at' => now()->subDays(1),
                'lost_reason' => null,
            ],
            [
                'name' => 'Andrea Conti',
                'company_name' => null,
                'email' => 'andrea.conti@gmail.com',
                'phone' => '+39 348 222 7766',
                'source' => LeadSource::Website,
                'status' => LeadStatus::Contacted,
                'estimated_value_cents' => 120_000,
                'notes' => 'Personal branding di un consulente finanziario.',
                'next_action_at' => now()->addDays(1),
                'last_contacted_at' => now()->subDays(20),
                'lost_reason' => null,
            ],
            [
                'name' => 'Palestra FitMilano',
                'company_name' => 'FitMilano S.r.l.',
                'email' => 'manager@fitmilano.it',
                'phone' => '+39 02 8877 4433',
                'source' => LeadSource::Event,
                'status' => LeadStatus::New,
                'estimated_value_cents' => 90_000,
                'notes' => 'Conosciuti al meetup WordPress Milano.',
                'next_action_at' => now()->addDays(3),
                'last_contacted_at' => null,
                'lost_reason' => null,
            ],
            [
                'name' => 'Studio Architetti Bianchi',
                'company_name' => 'Studio Bianchi & Associati',
                'email' => 'info@studiobianchi.archi',
                'phone' => '+39 06 9988 7766',
                'source' => LeadSource::ColdOutreach,
                'status' => LeadStatus::Lost,
                'estimated_value_cents' => 200_000,
                'notes' => 'Hanno scelto un\'altra agenzia per ragioni di budget.',
                'next_action_at' => null,
                'last_contacted_at' => now()->subDays(35),
                'lost_reason' => LostReason::Budget,
            ],
        ];

        foreach ($leads as $row) {
            Lead::query()->updateOrCreate(
                ['email' => $row['email']],
                array_merge($row, [
                    'source' => $row['source']->value,
                    'status' => $row['status']->value,
                    'lost_reason' => $row['lost_reason']?->value,
                    'estimated_value_currency' => 'EUR',
                    'owner_user_id' => $ownerId,
                ]),
            );
        }
    }
}
