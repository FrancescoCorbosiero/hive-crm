<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Services\Public;

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Events\ContactCreated;
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
    /**
     * Create a Contact from a payload originating outside the domain
     * (e.g. the Leads convert action). Roles default to ['customer'] —
     * pass an explicit `roles` key to override.
     *
     * Dispatches ContactCreated.
     *
     * @param  array{
     *     name: string,
     *     email?: ?string,
     *     phone?: ?string,
     *     vat_number?: ?string,
     *     tax_code?: ?string,
     *     address?: ?array<string,mixed>,
     *     notes?: ?string,
     *     roles?: array<int,string>,
     *     do_not_email?: bool,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function create(array $attributes): ContactDTO
    {
        $attributes = array_merge([
            'roles' => [ContactRole::Customer->value],
            'do_not_email' => false,
        ], $attributes);

        $contact = Contact::query()->create($attributes);

        ContactCreated::dispatch($contact->id);

        return ContactDTO::fromModel($contact);
    }

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
