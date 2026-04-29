<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Database\Seeders;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use Illuminate\Database\Seeder;

class ContactsSeeder extends Seeder
{
    /**
     * Seed a small but realistic IT-flavored contact book so the dashboard
     * doesn't look empty on first boot.
     */
    public function run(): void
    {
        $real = [
            // Customers
            [
                'name' => 'Pasticceria Bellavista S.r.l.',
                'email' => 'amministrazione@bellavistadolci.it',
                'phone' => '+39 02 8765 4321',
                'vat_number' => 'IT04827193045',
                'tax_code' => null,
                'address' => ['street' => 'Via Manzoni 12', 'city' => 'Milano', 'province' => 'MI', 'postal_code' => '20121', 'country' => 'IT'],
                'roles' => [ContactRole::Customer->value],
            ],
            [
                'name' => 'Studio Legale Rossi & Bianchi',
                'email' => 'info@rossiebianchi.legal',
                'phone' => '+39 06 4456 7890',
                'vat_number' => 'IT09384712095',
                'address' => ['street' => 'Via del Corso 145', 'city' => 'Roma', 'province' => 'RM', 'postal_code' => '00187', 'country' => 'IT'],
                'roles' => [ContactRole::Customer->value],
            ],
            [
                'name' => 'Marco Bertolini',
                'email' => 'marco.bertolini@gmail.com',
                'phone' => '+39 333 142 8765',
                'tax_code' => 'BRTMRC85L23F205X',
                'address' => ['street' => 'Via Garibaldi 3', 'city' => 'Bologna', 'province' => 'BO', 'postal_code' => '40121', 'country' => 'IT'],
                'roles' => [ContactRole::Customer->value],
            ],
            // Customer who is also a collaborator
            [
                'name' => 'Chiara Romano',
                'email' => 'chiara@romanodesign.it',
                'phone' => '+39 347 991 0123',
                'tax_code' => 'RMNCHR90M55H501Z',
                'address' => ['street' => 'Corso Vittorio Emanuele 88', 'city' => 'Napoli', 'province' => 'NA', 'postal_code' => '80132', 'country' => 'IT'],
                'roles' => [ContactRole::Customer->value, ContactRole::Collaborator->value],
                'notes' => 'Designer collaborativa per progetti di branding.',
            ],
            // Vendors
            [
                'name' => 'Aruba S.p.A.',
                'email' => 'fatture@aruba.it',
                'phone' => '+39 0575 0500',
                'vat_number' => 'IT01573850517',
                'address' => ['street' => 'Via San Clemente 53', 'city' => 'Bibbiena', 'province' => 'AR', 'postal_code' => '52011', 'country' => 'IT'],
                'roles' => [ContactRole::Vendor->value],
                'notes' => 'Hosting e domini.',
            ],
            [
                'name' => 'Contabo GmbH',
                'email' => 'billing@contabo.com',
                'phone' => null,
                'vat_number' => 'DE286913894',
                'address' => ['street' => 'Aschauer Straße 32a', 'city' => 'München', 'province' => 'BY', 'postal_code' => '81549', 'country' => 'DE'],
                'roles' => [ContactRole::Vendor->value],
                'notes' => 'VPS produzione + Object Storage.',
            ],
            // Collaborator
            [
                'name' => 'Luca Marchetti',
                'email' => 'luca.marchetti@dev.studio',
                'phone' => '+39 339 564 1287',
                'tax_code' => 'MRCLCU88H17L219K',
                'address' => ['street' => 'Via Po 24', 'city' => 'Torino', 'province' => 'TO', 'postal_code' => '10124', 'country' => 'IT'],
                'roles' => [ContactRole::Collaborator->value],
                'notes' => 'Sviluppatore freelance per overflow di progetti.',
            ],
            [
                'name' => 'Acme Commercialisti S.t.p.',
                'email' => 'studio@acmecommercialisti.it',
                'phone' => '+39 02 1234 5678',
                'vat_number' => 'IT02938475061',
                'address' => ['street' => 'Via Dante 17', 'city' => 'Milano', 'province' => 'MI', 'postal_code' => '20121', 'country' => 'IT'],
                'roles' => [ContactRole::Vendor->value],
                'notes' => 'Studio commercialista — fatturazione mensile.',
            ],
        ];

        $ownerId = \App\Models\User::query()->value('id');

        foreach ($real as $row) {
            Contact::query()->updateOrCreate(
                ['email' => $row['email']],
                array_merge($row, ['owner_user_id' => $ownerId]),
            );
        }

        // A handful of randomized extras for table density.
        Contact::factory()->count(8)->create(['owner_user_id' => $ownerId]);
    }
}
