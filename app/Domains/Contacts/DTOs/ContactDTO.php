<?php

declare(strict_types=1);

namespace App\Domains\Contacts\DTOs;

use App\Domains\Contacts\Models\Contact;

/**
 * Read-only projection of a Contact for cross-domain consumption.
 *
 * Other domains MUST consume Contacts through this DTO via the
 * ContactsService — never by importing the Contact Eloquent model.
 */
final readonly class ContactDTO
{
    /**
     * @param  array<string,mixed>|null  $address
     * @param  array<int,string>  $roles
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $ragioneSociale,
        public ?string $email,
        public ?string $phone,
        public ?string $vatNumber,
        public ?string $taxCode,
        public ?string $sdiCode,
        public ?string $pecEmail,
        public ?array $address,
        public ?string $notes,
        public array $roles,
        public bool $doNotEmail,
    ) {}

    public static function fromModel(Contact $contact): self
    {
        return new self(
            id: $contact->id,
            name: $contact->name,
            ragioneSociale: $contact->ragione_sociale,
            email: $contact->email,
            phone: $contact->phone,
            vatNumber: $contact->vat_number,
            taxCode: $contact->tax_code,
            sdiCode: $contact->sdi_code,
            pecEmail: $contact->pec_email,
            address: $contact->address ? (array) $contact->address : null,
            notes: $contact->notes,
            roles: $contact->roles ?? [],
            doNotEmail: (bool) $contact->do_not_email,
        );
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function isMailable(): bool
    {
        return ! $this->doNotEmail && $this->email !== null && $this->email !== '';
    }
}
