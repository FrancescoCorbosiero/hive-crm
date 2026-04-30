<?php

declare(strict_types=1);

namespace App\Domains\Websites\Database\Seeders;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Models\Website;
use Illuminate\Database\Seeder;

class WebsitesSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = \App\Models\User::query()->value('id');

        // Resolve the seeded customer contacts through the public service
        // — keeps this seeder honest about the no-cross-domain-Eloquent-
        // imports rule AND sidesteps the SQLite-vs-Postgres jsonb LIKE
        // portability gotcha (Postgres rejects LIKE on jsonb columns).
        $contacts = app(ContactsService::class);

        $resolveByEmail = function (string $email) use ($contacts): ?int {
            $id = $contacts->idsByEmails([$email])->first();

            return $id ? (int) $id : null;
        };

        $bellavista = $resolveByEmail('amministrazione@bellavistadolci.it');
        $rossi = $resolveByEmail('info@rossiebianchi.legal');
        $bertolini = $resolveByEmail('marco.bertolini@gmail.com');
        $romano = $resolveByEmail('chiara@romanodesign.it');

        $websites = [
            [
                'name' => ['it' => 'Pasticceria Bellavista — Sito istituzionale', 'en' => 'Pasticceria Bellavista — Corporate site'],
                'notes' => ['it' => 'Sito vetrina con e-commerce per ordini di torte personalizzate.', 'en' => 'Showcase site with e-commerce for custom cake orders.'],
                'url' => 'https://bellavistadolci.it',
                'status' => WebsiteStatus::Active,
                'owner_contact_id' => $bellavista,
                'tech_stack' => ['Laravel', 'Filament', 'Tailwind', 'Stripe'],
                'subscription_started_at' => now()->subYears(2)->subMonths(1),
                'next_renewal_at' => now()->addDays(12)->startOfDay(),
                'renewal_period_months' => 12,
            ],
            [
                'name' => ['it' => 'Studio Legale Rossi & Bianchi', 'en' => 'Rossi & Bianchi Law Firm'],
                'notes' => ['it' => 'Sito istituzionale + portale clienti con autenticazione.', 'en' => 'Corporate site with authenticated client portal.'],
                'url' => 'https://rossiebianchi.legal',
                'status' => WebsiteStatus::Active,
                'owner_contact_id' => $rossi,
                'tech_stack' => ['Laravel', 'Livewire', 'Tailwind'],
                'subscription_started_at' => now()->subYear(),
                'next_renewal_at' => now()->addDays(28)->startOfDay(),
                'renewal_period_months' => 12,
            ],
            [
                'name' => ['it' => 'Bertolini Photography Portfolio', 'en' => 'Bertolini Photography Portfolio'],
                'notes' => ['it' => 'Portfolio fotografico statico, hosting condiviso.', 'en' => 'Static photography portfolio, shared hosting.'],
                'url' => 'https://marcobertolini.photo',
                'status' => WebsiteStatus::Active,
                'owner_contact_id' => $bertolini,
                'tech_stack' => ['Astro', 'Tailwind'],
                'subscription_started_at' => now()->subMonths(8),
                'next_renewal_at' => now()->addMonths(4)->startOfDay(),
                'renewal_period_months' => 12,
            ],
            [
                'name' => ['it' => 'Romano Design — Studio brand', 'en' => 'Romano Design — Brand studio'],
                'notes' => ['it' => 'Sito di Chiara con CMS leggero per case study.', 'en' => 'Chiara\'s site with a lightweight CMS for case studies.'],
                'url' => 'https://romanodesign.it',
                'status' => WebsiteStatus::Active,
                'owner_contact_id' => $romano,
                'tech_stack' => ['Next.js', 'Tailwind'],
                'subscription_started_at' => now()->subYears(3),
                'next_renewal_at' => now()->addDays(6)->startOfDay(),
                'renewal_period_months' => 12,
            ],
            [
                'name' => ['it' => 'Hive Internal Tools', 'en' => 'Hive Internal Tools'],
                'notes' => ['it' => 'Strumenti interni — non fatturato.', 'en' => 'Internal tools — not billed.'],
                'url' => 'https://internal.hive.local',
                'status' => WebsiteStatus::Maintenance,
                'owner_contact_id' => null,
                'tech_stack' => ['Laravel', 'Filament'],
                'subscription_started_at' => now()->subYear(),
                'next_renewal_at' => now()->addMonths(6)->startOfDay(),
                'renewal_period_months' => 12,
            ],
            [
                'name' => ['it' => 'Old Cliente — Sito archiviato', 'en' => 'Old Client — Archived site'],
                'notes' => ['it' => 'Cliente uscito dal portafoglio nel 2024.', 'en' => 'Client churned in 2024.'],
                'url' => 'https://old-client-archive.it',
                'status' => WebsiteStatus::Archived,
                'owner_contact_id' => null,
                'tech_stack' => ['WordPress'],
                'subscription_started_at' => now()->subYears(4),
                'next_renewal_at' => null,
                'renewal_period_months' => 12,
            ],
        ];

        foreach ($websites as $row) {
            Website::query()->updateOrCreate(
                ['url' => $row['url']],
                array_merge($row, ['owner_user_id' => $ownerId]),
            );
        }
    }
}
