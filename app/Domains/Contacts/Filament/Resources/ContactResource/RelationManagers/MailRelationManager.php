<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Models\CampaignRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Latest 10 campaign sends to this contact, newest first. Pending
 * rows are hidden — we want signal about what's actually landed in
 * the inbox, not what's still queued.
 */
class MailRelationManager extends RelationManager
{
    protected static string $relationship = 'campaignRecipients';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.mail');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('status', '!=', RecipientStatus::Pending->value)
                ->orderByDesc('sent_at')
                ->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label(__('mail/campaigns.fields.name'))
                    ->default('—')
                    ->limit(40),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('contacts/labels.summary.sent_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('contacts/labels.summary.mail_status'))
                    ->badge()
                    ->formatStateUsing(fn (RecipientStatus $state) => $state->label())
                    ->color(fn (RecipientStatus $state) => match ($state) {
                        RecipientStatus::Opened, RecipientStatus::Clicked, RecipientStatus::Delivered => 'success',
                        RecipientStatus::Bounced, RecipientStatus::Complained, RecipientStatus::Failed => 'danger',
                        RecipientStatus::Unsubscribed, RecipientStatus::Skipped => 'warning',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('contacts/labels.summary.mail_empty'));
    }
}
