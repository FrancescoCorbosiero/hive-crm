<?php

declare(strict_types=1);

namespace App\Shared\Filament;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Events\ContactCreated;
use App\Domains\Contacts\Models\Contact;
use Filament\Forms;
use Filament\Forms\Components\Select;

/**
 * Reusable contact picker. Behaves like the WordPress/Shopify
 * "+ Add new" pattern: typing in the search box lets you find an
 * existing contact, or click the inline create button to open a
 * minimal-fields modal. The created contact is auto-selected.
 *
 * The contact created inline is tagged with the role(s) that fit
 * the calling context (customer for invoicing flows, vendor for
 * expense flows). Roles can be passed at the call site:
 *
 *     ContactPicker::make('client_contact_id', [ContactRole::Customer])
 *     ContactPicker::make('vendor_contact_id', [ContactRole::Vendor])
 *
 * Search is case-insensitive across `name` and `email` and runs
 * against the contacts table via LOWER(...) LIKE so it works on
 * both Postgres (prod) and SQLite (tests).
 */
class ContactPicker
{
    /**
     * @param  list<ContactRole|string>  $defaultRoles
     *         Roles assigned to newly-created contacts.
     */
    public static function make(string $name, array $defaultRoles = [ContactRole::Customer]): Select
    {
        $defaultRoleValues = array_map(
            fn ($r) => $r instanceof ContactRole ? $r->value : $r,
            $defaultRoles,
        );

        return Select::make($name)
            ->searchable()
            ->preload()
            ->options(fn () => Contact::query()->orderBy('name')->limit(50)->pluck('name', 'id'))
            ->getSearchResultsUsing(function (string $search) {
                $term = '%'.mb_strtolower(trim($search)).'%';

                return Contact::query()
                    ->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(COALESCE(ragione_sociale, \'\')) LIKE ?', [$term]);
                    })
                    ->orderBy('name')
                    ->limit(50)
                    ->pluck('name', 'id');
            })
            ->getOptionLabelUsing(fn ($value) => Contact::query()->whereKey($value)->value('name'))
            ->createOptionForm(self::createOptionForm())
            ->createOptionUsing(function (array $data) use ($defaultRoleValues): int {
                $contact = Contact::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'ragione_sociale' => $data['ragione_sociale'] ?: null,
                    'vat_number' => $data['vat_number'] ?: null,
                    'roles' => $defaultRoleValues,
                    'do_not_email' => false,
                ]);

                ContactCreated::dispatch($contact->id);

                return $contact->id;
            });
    }

    /**
     * @return array<int, \Filament\Forms\Components\Field>
     */
    private static function createOptionForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('contacts/labels.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('email')
                ->label(__('contacts/labels.email'))
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->label(__('contacts/labels.phone'))
                ->maxLength(64),

            Forms\Components\TextInput::make('ragione_sociale')
                ->label(__('contacts/labels.ragione_sociale'))
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('vat_number')
                ->label(__('contacts/labels.vat_number'))
                ->maxLength(32),
        ];
    }
}
