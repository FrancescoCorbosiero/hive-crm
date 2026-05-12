<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\Pages;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Filament\Resources\ContactResource;
use App\Domains\Contacts\Models\Contact;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/**
 * Customer 360 — single page showing identity, fiscal and address
 * data alongside cross-domain activity (quotations, fatture, calendar
 * events, mail). The activity panels are mounted as relation managers
 * via ContactResource::getRelations() so they appear under this page.
 */
class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make(__('contacts/labels.section.identity'))
                ->columns(3)
                ->schema([
                    Components\TextEntry::make('name')
                        ->label(__('contacts/labels.name')),
                    Components\TextEntry::make('ragione_sociale')
                        ->label(__('contacts/labels.ragione_sociale'))
                        ->placeholder('—'),
                    Components\TextEntry::make('roles')
                        ->label(__('contacts/labels.roles'))
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ContactRole::tryFrom($state)?->label() ?? $state)
                        ->color(fn (string $state) => ContactRole::tryFrom($state)?->color() ?? 'gray'),
                    Components\TextEntry::make('email')
                        ->label(__('contacts/labels.email'))
                        ->copyable()
                        ->placeholder('—'),
                    Components\TextEntry::make('phone')
                        ->label(__('contacts/labels.phone'))
                        ->placeholder('—'),
                    Components\IconEntry::make('do_not_email')
                        ->label(__('contacts/labels.do_not_email'))
                        ->boolean()
                        ->trueIcon('heroicon-o-no-symbol')
                        ->trueColor('danger')
                        ->falseIcon('heroicon-o-envelope')
                        ->falseColor('success'),
                ]),

            Components\Section::make(__('contacts/labels.section.tax'))
                ->columns(2)
                ->schema([
                    Components\TextEntry::make('vat_number')
                        ->label(__('contacts/labels.vat_number'))
                        ->placeholder('—'),
                    Components\TextEntry::make('tax_code')
                        ->label(__('contacts/labels.tax_code'))
                        ->placeholder('—'),
                    Components\TextEntry::make('sdi_code')
                        ->label(__('contacts/labels.sdi_code'))
                        ->placeholder('—'),
                    Components\TextEntry::make('pec_email')
                        ->label(__('contacts/labels.pec_email'))
                        ->placeholder('—'),
                ])
                ->collapsible()
                ->visible(fn (Contact $r) => $r->vat_number || $r->tax_code || $r->sdi_code || $r->pec_email),

            Components\Section::make(__('contacts/labels.section.address'))
                ->columns(3)
                ->schema([
                    Components\TextEntry::make('address.street')
                        ->label(__('contacts/labels.address.street'))
                        ->columnSpan(3)
                        ->placeholder('—'),
                    Components\TextEntry::make('address.city')
                        ->label(__('contacts/labels.address.city'))
                        ->placeholder('—'),
                    Components\TextEntry::make('address.province')
                        ->label(__('contacts/labels.address.province'))
                        ->placeholder('—'),
                    Components\TextEntry::make('address.postal_code')
                        ->label(__('contacts/labels.address.postal_code'))
                        ->placeholder('—'),
                ])
                ->collapsed()
                ->visible(fn (Contact $r) => $r->address !== null),

            Components\Section::make(__('contacts/labels.summary.notes'))
                ->schema([
                    Components\TextEntry::make('notes')
                        ->label('')
                        ->prose()
                        ->placeholder('—'),
                ])
                ->collapsed()
                ->visible(fn (Contact $r) => ! empty($r->notes)),
        ]);
    }
}
