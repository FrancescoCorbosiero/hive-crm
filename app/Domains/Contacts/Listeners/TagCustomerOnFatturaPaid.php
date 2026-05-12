<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Listeners;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Events\FatturaPaid;

/**
 * When any fattura is fully paid, ensure the billed contact carries
 * the Customer role. Useful for contacts created outside the Lead
 * pipeline — imports, manual entries, fatture issued without a
 * preceding Lead. Idempotent thanks to Contact::assignRole.
 */
class TagCustomerOnFatturaPaid
{
    public function handle(FatturaPaid $event): void
    {
        $contact = Contact::query()->find($event->clientContactId);

        if ($contact === null) {
            return;
        }

        if ($contact->hasRole(ContactRole::Customer)) {
            return;
        }

        $contact->assignRole(ContactRole::Customer)->save();
    }
}
