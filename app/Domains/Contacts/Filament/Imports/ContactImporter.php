<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Imports;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('ragione_sociale')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('email')
                ->rules(['nullable', 'email']),
            ImportColumn::make('phone'),
            ImportColumn::make('vat_number'),
            ImportColumn::make('tax_code'),
            ImportColumn::make('sdi_code')
                ->rules(['nullable', 'string', 'max:7']),
            ImportColumn::make('pec_email')
                ->rules(['nullable', 'email']),

            // Roles arrive as a comma-separated string (customer,vendor) or
            // pipe-separated. Whitespace tolerant. Falls back to ['customer']
            // if the column is missing or empty.
            ImportColumn::make('roles')
                ->castStateUsing(function (?string $state): array {
                    if (! $state) {
                        return [ContactRole::Customer->value];
                    }
                    $parts = preg_split('/[,|]/', $state) ?: [];
                    $roles = array_filter(array_map(fn ($s) => strtolower(trim($s)), $parts));
                    $valid = array_map(fn (ContactRole $r) => $r->value, ContactRole::cases());

                    return array_values(array_intersect($valid, $roles)) ?: [ContactRole::Customer->value];
                }),

            // Address as a single street/city/province/postal_code/country string
            // delimited by " | " is fine for v1; advanced users can edit after.
            ImportColumn::make('address')
                ->castStateUsing(function (?string $state): ?array {
                    if (! $state) {
                        return null;
                    }
                    $parts = array_map('trim', explode('|', $state));

                    return [
                        'street' => $parts[0] ?? null,
                        'city' => $parts[1] ?? null,
                        'province' => $parts[2] ?? null,
                        'postal_code' => $parts[3] ?? null,
                        'country' => $parts[4] ?? 'IT',
                    ];
                }),

            ImportColumn::make('do_not_email')
                ->boolean(),
            ImportColumn::make('notes'),
        ];
    }

    public function resolveRecord(): ?Contact
    {
        // Upsert by email to make CSV re-imports idempotent — re-running
        // the same file updates rather than duplicates.
        if (! empty($this->data['email'])) {
            return Contact::query()->firstOrNew(['email' => $this->data['email']]);
        }

        return new Contact();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Imported '.number_format($import->successful_rows).' contact(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' Failed rows: '.number_format($failedRowsCount).'.';
        }

        return $body;
    }
}
