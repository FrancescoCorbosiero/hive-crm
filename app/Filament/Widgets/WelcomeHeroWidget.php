<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Leads\Models\Lead;
use App\Domains\Websites\Models\Website;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Welcome hero — replaces Filament's default AccountWidget with a
 * personalised, visually-prominent greeting card showing time-of-day
 * salutation, the current date in the active locale, and a strip of
 * top-of-mind counters (open leads, pending invoices, active sites).
 */
class WelcomeHeroWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-hero';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public function getGreetingProperty(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 12 => __('dashboard.hero.greeting.morning'),
            $hour < 18 => __('dashboard.hero.greeting.afternoon'),
            default => __('dashboard.hero.greeting.evening'),
        };
    }

    public function getUserNameProperty(): string
    {
        return Filament::auth()->user()?->name ?? '';
    }

    public function getTodayLabelProperty(): string
    {
        return Carbon::now()
            ->locale(app()->getLocale())
            ->isoFormat('dddd D MMMM YYYY');
    }

    /**
     * @return array<int, array{label: string, value: int|string, color: string, icon: string, url: ?string}>
     */
    public function getCountersProperty(): array
    {
        $openLeads = Lead::query()->open()->count();
        $unpaidInvoices = Fattura::query()
            ->whereIn('payment_status', ['unpaid', 'partially_paid', 'overdue'])
            ->count();
        $activeWebsites = Website::query()->active()->count();
        $contacts = Contact::query()->count();

        return [
            [
                'label' => __('dashboard.hero.counters.open_leads'),
                'value' => $openLeads,
                'color' => 'primary',
                'icon' => 'heroicon-o-funnel',
            ],
            [
                'label' => __('dashboard.hero.counters.unpaid_invoices'),
                'value' => $unpaidInvoices,
                'color' => 'danger',
                'icon' => 'heroicon-o-document-currency-euro',
            ],
            [
                'label' => __('dashboard.hero.counters.active_websites'),
                'value' => $activeWebsites,
                'color' => 'success',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'label' => __('dashboard.hero.counters.contacts'),
                'value' => $contacts,
                'color' => 'info',
                'icon' => 'heroicon-o-user-group',
            ],
        ];
    }
}
