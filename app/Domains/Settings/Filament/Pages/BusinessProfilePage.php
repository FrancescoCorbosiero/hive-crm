<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament\Pages;

use App\Domains\Settings\Enums\ProfileType;
use App\Domains\Settings\Models\BusinessProfile;
use App\Domains\Settings\Services\Public\BusinessProfileService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Singleton settings page — edits the one `business_profile` row.
 *
 * Filament's Resource pages assume a multi-row CRUD, which is wrong
 * for a one-of-its-kind entity. This is a Page that hosts a Form
 * bound to the singleton instance.
 */
class BusinessProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.business-profile';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed> */
    public array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings/profile.page_title');
    }

    public function getTitle(): string
    {
        return __('settings/profile.page_title');
    }

    public function getSubheading(): ?string
    {
        return __('settings/profile.subtitle');
    }

    public function mount(): void
    {
        $profile = app(BusinessProfileService::class)->singleton();
        $this->form->fill($profile->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('business_profile_tabs')
                    ->tabs([

                        Forms\Components\Tabs\Tab::make(__('settings/profile.tabs.anagrafica'))
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->label(__('settings/profile.fields.type'))
                                            ->options(ProfileType::options())
                                            ->default(ProfileType::DittaIndividuale->value)
                                            ->required(),
                                        Forms\Components\TextInput::make('denominazione')
                                            ->label(__('settings/profile.fields.denominazione'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('nome')
                                            ->label(__('settings/profile.fields.nome'))
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('cognome')
                                            ->label(__('settings/profile.fields.cognome'))
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('codice_fiscale')
                                            ->label(__('settings/profile.fields.codice_fiscale'))
                                            ->maxLength(32),
                                        Forms\Components\TextInput::make('partita_iva')
                                            ->label(__('settings/profile.fields.partita_iva'))
                                            ->maxLength(32),
                                        Forms\Components\TextInput::make('regime_fiscale')
                                            ->label(__('settings/profile.fields.regime_fiscale'))
                                            ->placeholder('RF19')
                                            ->maxLength(8),
                                        Forms\Components\TextInput::make('natura_default')
                                            ->label(__('settings/profile.fields.natura_default'))
                                            ->placeholder('N2.2')
                                            ->maxLength(8),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('settings/profile.tabs.sede'))
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('sede_indirizzo')
                                            ->label(__('settings/profile.fields.sede_indirizzo'))
                                            ->columnSpan(2)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('sede_civico')
                                            ->label(__('settings/profile.fields.sede_civico'))
                                            ->maxLength(16),
                                        Forms\Components\TextInput::make('sede_cap')
                                            ->label(__('settings/profile.fields.sede_cap'))
                                            ->maxLength(16),
                                        Forms\Components\TextInput::make('sede_comune')
                                            ->label(__('settings/profile.fields.sede_comune'))
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('sede_provincia')
                                            ->label(__('settings/profile.fields.sede_provincia'))
                                            ->maxLength(4),
                                        Forms\Components\TextInput::make('sede_nazione')
                                            ->label(__('settings/profile.fields.sede_nazione'))
                                            ->default('IT')
                                            ->maxLength(4),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('settings/profile.tabs.contatti'))
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label(__('settings/profile.fields.email'))
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('pec_email')
                                            ->label(__('settings/profile.fields.pec_email'))
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('settings/profile.fields.phone'))
                                            ->maxLength(64),
                                        Forms\Components\TextInput::make('website_url')
                                            ->label(__('settings/profile.fields.website_url'))
                                            ->url()
                                            ->maxLength(255),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('settings/profile.tabs.conti'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Repeater::make('bank_accounts')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('settings/profile.bank.name'))
                                            ->placeholder(__('settings/profile.bank.name_placeholder'))
                                            ->required()
                                            ->maxLength(64)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('iban')
                                            ->label(__('settings/profile.bank.iban'))
                                            ->required()
                                            ->maxLength(34)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('bic')
                                            ->label(__('settings/profile.bank.bic'))
                                            ->maxLength(16),
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label(__('settings/profile.bank.bank_name'))
                                            ->maxLength(128),
                                        Forms\Components\TextInput::make('account_holder')
                                            ->label(__('settings/profile.bank.account_holder'))
                                            ->maxLength(128)
                                            ->columnSpan(2),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label(__('settings/profile.bank.is_primary'))
                                            ->helperText(__('settings/profile.bank.is_primary_help'))
                                            ->columnSpan(2),
                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('settings/profile.bank.notes'))
                                            ->rows(2)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(2)
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->collapsible()
                                    ->cloneable()
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->addActionLabel(__('settings/profile.bank.add')),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('settings/profile.tabs.note'))
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('')
                                    ->rows(6),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $accounts = $data['bank_accounts'] ?? [];

        // Exactly one primary. If the user marks multiple as primary,
        // keep the first one; if none, promote the first row to primary
        // so downstream consumers (FatturaPA exporter, PDF template)
        // always have a default to fall back to.
        $primarySeen = false;
        $accounts = array_values(array_map(function (array $row) use (&$primarySeen): array {
            if (! empty($row['is_primary']) && ! $primarySeen) {
                $primarySeen = true;
                $row['is_primary'] = true;
            } else {
                $row['is_primary'] = false;
            }

            return $row;
        }, $accounts));

        if (! $primarySeen && $accounts !== []) {
            $accounts[0]['is_primary'] = true;
        }

        $data['bank_accounts'] = $accounts;

        $profile = app(BusinessProfileService::class)->singleton();
        $profile->update($data);

        Notification::make()
            ->success()
            ->title(__('settings/profile.saved'))
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label(__('settings/profile.save'))
                ->submit('save'),
        ];
    }
}
