<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Resources;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Filament\Resources\CampaignResource\Pages;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Services\Public\MailService;
use DomainException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    use Translatable;

    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.mail');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.mail');
    }

    public static function getModelLabel(): string
    {
        return __('mail/campaigns.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail/campaigns.plural');
    }

    public static function getTranslatableLocales(): array
    {
        return config('app.supported_locales', ['it', 'en']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('mail/campaigns.sections.content'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('mail/campaigns.fields.name'))
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('subject')
                        ->label(__('mail/campaigns.fields.subject'))
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\RichEditor::make('body_html')
                        ->label(__('mail/campaigns.fields.body_html'))
                        ->helperText('Tag disponibili: {{name}}, {{email}}, {{unsubscribe_url}}')
                        ->columnSpan(2),
                ]),

            Forms\Components\Section::make(__('mail/campaigns.sections.schedule'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label(__('mail/campaigns.fields.status'))
                        ->options(CampaignStatus::options())
                        ->default(CampaignStatus::Draft->value)
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label(__('mail/campaigns.fields.scheduled_at'))
                        ->seconds(false)
                        ->displayFormat('d/m/Y H:i'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('mail/campaigns.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('mail/campaigns.fields.status'))
                    ->badge()
                    ->color(fn (CampaignStatus $state) => $state->color())
                    ->formatStateUsing(fn (CampaignStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('#'),
                Tables\Columns\TextColumn::make('sent_count')
                    ->label(__('mail/campaigns.fields.sent_count'))
                    ->color('info'),
                Tables\Columns\TextColumn::make('delivered_count')
                    ->label(__('mail/campaigns.fields.delivered_count'))
                    ->color('success'),
                Tables\Columns\TextColumn::make('bounced_count')
                    ->label(__('mail/campaigns.fields.bounced_count'))
                    ->color('danger'),
                Tables\Columns\TextColumn::make('complained_count')
                    ->label(__('mail/campaigns.fields.complained_count'))
                    ->color('danger')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('opened_count')
                    ->label(__('mail/campaigns.fields.opened_count'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('clicked_count')
                    ->label(__('mail/campaigns.fields.clicked_count'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('mail/campaigns.fields.sent_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('mail/campaigns.fields.status'))
                    ->options(CampaignStatus::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::sendNowAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function sendNowAction(): Action
    {
        return Action::make('sendNow')
            ->label(__('mail/campaigns.actions.send_now'))
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->visible(fn (Campaign $c) => ! $c->status->isFinal())
            ->form([
                Forms\Components\Select::make('contact_ids')
                    ->label(__('app.navigation.contacts'))
                    ->multiple()
                    ->options(fn () => Contact::query()
                        ->where('do_not_email', false)
                        ->whereNotNull('email')
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (Campaign $campaign, array $data) {
                try {
                    app(MailService::class)->dispatchCampaign(
                        $campaign->id,
                        array_map('intval', $data['contact_ids']),
                    );
                } catch (DomainException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('mail/campaigns.notifications.cannot_send_final'))
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('mail/campaigns.notifications.dispatched_title'))
                    ->body(__('mail/campaigns.notifications.dispatched_body'))
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
