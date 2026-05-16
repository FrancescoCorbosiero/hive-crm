<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Catalog\Filament\Resources\ServiceResource;
use App\Domains\Contacts\Filament\Resources\ContactResource;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\Documents\Filament\Resources\FatturaResource;
use App\Domains\Finance\Filament\Resources\FinancialEntryResource;
use App\Domains\Leads\Filament\Resources\LeadResource;
use App\Domains\Quotations\Filament\Resources\QuotationResource;
use App\Domains\Websites\Filament\Resources\WebsiteResource;
use Filament\Widgets\Widget;

/**
 * Quick Actions tile grid — surfaces the most frequent create flows
 * as visually-prominent tiles right at the top of the dashboard so the
 * user is never more than one click away from inserting the entities
 * they touch every day.
 *
 * All tiles deep-link to the corresponding Filament resource create
 * page; the dashboard header still hosts the three slide-over
 * "fast entry" actions (record payment / add lead / log expense) for
 * one-shot logging without navigating away.
 */
class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.quick_actions.heading');
    }

    public function getDescription(): string
    {
        return __('dashboard.quick_actions.description');
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     accent: string,
     *     url: string,
     * }>
     */
    public function getTilesProperty(): array
    {
        return [
            [
                'key' => 'contact',
                'label' => __('dashboard.quick_actions.tiles.contact.label'),
                'description' => __('dashboard.quick_actions.tiles.contact.description'),
                'icon' => 'heroicon-o-user-plus',
                'accent' => 'sky',
                'url' => ContactResource::getUrl('create'),
            ],
            [
                'key' => 'website',
                'label' => __('dashboard.quick_actions.tiles.website.label'),
                'description' => __('dashboard.quick_actions.tiles.website.description'),
                'icon' => 'heroicon-o-globe-alt',
                'accent' => 'emerald',
                'url' => WebsiteResource::getUrl('create'),
            ],
            [
                'key' => 'lead',
                'label' => __('dashboard.quick_actions.tiles.lead.label'),
                'description' => __('dashboard.quick_actions.tiles.lead.description'),
                'icon' => 'heroicon-o-funnel',
                'accent' => 'amber',
                'url' => LeadResource::getUrl('create'),
            ],
            [
                'key' => 'quotation',
                'label' => __('dashboard.quick_actions.tiles.quotation.label'),
                'description' => __('dashboard.quick_actions.tiles.quotation.description'),
                'icon' => 'heroicon-o-clipboard-document-list',
                'accent' => 'violet',
                'url' => QuotationResource::getUrl('create'),
            ],
            [
                'key' => 'fattura',
                'label' => __('dashboard.quick_actions.tiles.fattura.label'),
                'description' => __('dashboard.quick_actions.tiles.fattura.description'),
                'icon' => 'heroicon-o-document-text',
                'accent' => 'indigo',
                'url' => FatturaResource::getUrl('create'),
            ],
            [
                'key' => 'domain',
                'label' => __('dashboard.quick_actions.tiles.domain.label'),
                'description' => __('dashboard.quick_actions.tiles.domain.description'),
                'icon' => 'heroicon-o-server-stack',
                'accent' => 'cyan',
                'url' => DomainNameResource::getUrl('create'),
            ],
            [
                'key' => 'expense',
                'label' => __('dashboard.quick_actions.tiles.expense.label'),
                'description' => __('dashboard.quick_actions.tiles.expense.description'),
                'icon' => 'heroicon-o-receipt-percent',
                'accent' => 'rose',
                'url' => FinancialEntryResource::getUrl('create'),
            ],
            [
                'key' => 'service',
                'label' => __('dashboard.quick_actions.tiles.service.label'),
                'description' => __('dashboard.quick_actions.tiles.service.description'),
                'icon' => 'heroicon-o-squares-plus',
                'accent' => 'fuchsia',
                'url' => ServiceResource::getUrl('create'),
            ],
        ];
    }
}
