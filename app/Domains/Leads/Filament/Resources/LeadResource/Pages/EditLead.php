<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Resources\LeadResource\Pages;

use App\Domains\Leads\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->bookCallAction(),
            Actions\DeleteAction::make(),
        ];
    }

    private function bookCallAction(): Actions\Action
    {
        return Actions\Action::make('book_call')
            ->label(__('leads/labels.book_call.action'))
            ->icon('heroicon-o-calendar-days')
            ->color('info')
            ->visible(fn (): bool => filled(config('services.calcom.public_link')))
            ->url(fn (): string => $this->buildCalcomUrl())
            ->openUrlInNewTab();
    }

    private function buildCalcomUrl(): string
    {
        $link = (string) config('services.calcom.public_link');

        $params = array_filter([
            'name' => $this->record->name,
            'email' => $this->record->email,
        ], fn ($v) => filled($v));

        if ($params === []) {
            return $link;
        }

        return $link.(str_contains($link, '?') ? '&' : '?').http_build_query($params);
    }
}
