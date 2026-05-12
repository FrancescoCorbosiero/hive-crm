<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Document;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Leads\Models\Lead;
use App\Domains\Websites\Models\Website;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DemoDataPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static string $view = 'filament.pages.demo-data';

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('demo_data.page_title');
    }

    public function getTitle(): string
    {
        return __('demo_data.page_title');
    }

    public function getSubheading(): ?string
    {
        return __('demo_data.subtitle');
    }

    /**
     * Heuristic counts so the page shows what's currently in each table.
     * Demo data isn't tagged — we can't tell "demo" rows from "real"
     * ones, so we just show totals and let the user judge.
     *
     * @return array<string, int>
     */
    public function getTableCountsProperty(): array
    {
        return [
            'contacts' => Contact::query()->count(),
            'websites' => Website::query()->count(),
            'financial_entries' => FinancialEntry::query()->count(),
            'leads' => Lead::query()->count(),
            'documents' => Document::query()->count(),
        ];
    }

    public function isWorkspaceEmpty(): bool
    {
        return array_sum($this->getTableCountsProperty()) === 0;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('install')
                ->label(__('demo_data.install.action'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('demo_data.install.modal_heading'))
                ->modalDescription(fn () => $this->isWorkspaceEmpty()
                    ? __('demo_data.install.modal_description_empty')
                    : __('demo_data.install.modal_description_non_empty'))
                ->modalSubmitActionLabel(__('demo_data.install.confirm'))
                ->action(function (): void {
                    try {
                        Artisan::call('db:seed', [
                            '--class' => \Database\Seeders\DemoDataSeeder::class,
                            '--force' => true,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('demo_data.install.success_title'))
                            ->body(__('demo_data.install.success_body'))
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('demo_data.install.failure_title'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
