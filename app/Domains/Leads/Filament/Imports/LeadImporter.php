<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Imports;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class LeadImporter extends Importer
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('email')->rules(['nullable', 'email']),
            ImportColumn::make('phone'),
            ImportColumn::make('source')
                ->castStateUsing(fn (?string $state) => $state
                    ? (LeadSource::tryFrom(strtolower(trim($state)))?->value ?? LeadSource::Other->value)
                    : null),
            ImportColumn::make('status')
                ->castStateUsing(fn (?string $state) => $state
                    ? (LeadStatus::tryFrom(strtolower(trim($state)))?->value ?? LeadStatus::New->value)
                    : LeadStatus::New->value),
            // Major-unit decimal in the CSV → integer cents in the DB.
            ImportColumn::make('estimated_value_cents')
                ->castStateUsing(fn ($state) => MoneyInput::majorToCents($state)),
            ImportColumn::make('next_action_at'),
            ImportColumn::make('notes'),
        ];
    }

    public function resolveRecord(): ?Lead
    {
        if (! empty($this->data['email'])) {
            return Lead::query()->firstOrNew(['email' => $this->data['email']]);
        }

        return new Lead();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' lead(s).';
    }
}
