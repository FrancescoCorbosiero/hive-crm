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
     * Search Contacts by name OR email, case-insensitive, limited to
     * mailable contacts (do_not_email=false, email IS NOT NULL).
     * Used by the MailTestPage recipient picker.
     *
     * @return Collection<int, ContactDTO>
     */
    public function searchMailable(string $query, int $limit = 20): Collection
    {
        $term = '%'.mb_strtolower(trim($query)).'%';

        return Contact::query()
            ->where('do_not_email', false)
            ->whereNotNull('email')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Contact $c) => ContactDTO::fromModel($c));
    }

    /**
     * Look up Contact IDs by email address, case-insensitive. Returns a
     * deduped collection — multiple Contacts with the same email
     * (legacy data) all match.
     *
     * @param  array<int,string>  $emails
     * @return Collection<int, int>
     */
    public function idsByEmails(array $emails): Collection
    {
        $emails = collect($emails)
            ->filter()
            ->map(fn (string $e) => mb_strtolower(trim($e)))
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return collect();
        }

        return Contact::query()
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(email)'), $emails)
            ->pluck('id');
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
