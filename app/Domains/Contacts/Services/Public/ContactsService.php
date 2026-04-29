<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Services\Public;

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Events\ContactFlaggedDoNotEmail;
use App\Domains\Contacts\Models\Contact;
use Illuminate\Support\Collection;

/**
 * Public entry-point for cross-domain access to the Contacts domain.
 *
 * Anything outside `App\Domains\Contacts\*` MUST go through this class.
 * Returns DTOs, never Eloquent models.
 */
class ContactsService
{
    public function find(int $id): ?ContactDTO
    {
        $contact = Contact::query()->find($id);

        return $contact ? ContactDTO::fromModel($contact) : null;
    }

    /**
     * @return Collection<int, ContactDTO>
     */
    public function findMany(array $ids): Collection
    {
        return Contact::query()
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (Contact $c) => ContactDTO::fromModel($c));
    }

    /**
     * @return Collection<int, ContactDTO>
     */
    public function withRole(ContactRole|string $role): Collection
    {
        return Contact::query()
            ->withRole($role)
            ->get()
            ->map(fn (Contact $c) => ContactDTO::fromModel($c));
    }

    /**
     * Mark a contact as do-not-email and emit the matching domain event.
     * Idempotent: re-flagging an already-flagged contact is a no-op event.
     */
    public function flagDoNotEmail(int $id, ?string $reason = null): void
    {
        $contact = Contact::query()->find($id);

        if (! $contact) {
            return;
        }

        if ($contact->do_not_email) {
            return;
        }

        $contact->do_not_email = true;
        $contact->save();

        ContactFlaggedDoNotEmail::dispatch($contact->id, $reason);
    }
}
