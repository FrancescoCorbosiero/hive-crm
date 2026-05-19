<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Pages;

use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Websites\Models\Website;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

/**
 * Cash flow projection — derives expected income / loss for the next
 * N months from existing schedule entities, WITHOUT writing any rows.
 *
 *   - Projected income comes from active RecurringFattura schedules
 *     (per-cycle total of lines × qty × price + VAT, repeated every
 *     advance() of the frequency).
 *   - Projected loss comes from active DomainName entries with a
 *     renewal_cost_cents and an expires_at — each renewal cycle is a
 *     LOSS at the expiry date.
 *   - Active Website entries with a renewal_cost_cents and a
 *     next_renewal_at contribute hosting losses on the same cycle
 *     pattern (renewal_period_months apart).
 *
 * No rows are created. The Italian fattura is a tax artefact whose
 * sequential numbering is allocated at issuance time — pre-creating
 * fatture would break that contract. This page is the "what's
 * coming" view; the existing per-event "register"/"log renewal"
 * actions remain the source of truth for what actually happened.
 */
class CashFlowProjectionPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.cash-flow-projection';

    /** @var array<string,mixed> */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('finance/projection.page_title');
    }

    public function getTitle(): string
    {
        return __('finance/projection.page_title');
    }

    public function mount(): void
    {
        $this->form->fill(['months' => 12]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('months')
                    ->label(__('finance/projection.fields.window'))
                    ->options([
                        3 => __('finance/projection.windows.3'),
                        6 => __('finance/projection.windows.6'),
                        12 => __('finance/projection.windows.12'),
                        24 => __('finance/projection.windows.24'),
                    ])
                    ->default(12)
                    ->required(),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string,mixed>
     */
    public function getViewData(): array
    {
        $months = (int) ($this->form->getState()['months'] ?? 12);
        $start = now()->copy()->startOfMonth();
        // Window has exactly $months buckets: $start month through
        // ($start + $months - 1). Anything past that drops off.
        $end = $start->copy()->addMonths($months - 1)->endOfMonth();
        $locale = app()->getLocale();
        $currency = (string) config('app.currency', 'EUR');

        // Initialise empty buckets per month so the chart has a row for
        // every month even when nothing's projected in it.
        $monthly = [];
        for ($i = 0; $i < $months; $i++) {
            $cursor = $start->copy()->addMonths($i);
            $monthly[$cursor->format('Y-m')] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('MMM YYYY'),
                'income_cents' => 0,
                'loss_cents' => 0,
            ];
        }

        $entries = [];

        // Projected income from active RecurringFattura schedules.
        foreach (RecurringFattura::query()->where('is_active', true)->get() as $rec) {
            $cycleCents = $this->cycleTotalCents((array) $rec->lines);
            if ($cycleCents <= 0) {
                continue;
            }

            $cursor = $rec->next_issue_at?->copy()?->startOfDay() ?? now()->copy()->startOfDay();

            // Iterate until we're past the projection window. We don't
            // skip "in the past" cycles — an operator who's behind on
            // issuing wants to see them flagged in month-1.
            while ($cursor->lte($end)) {
                $monthKey = $cursor->format('Y-m');
                if (isset($monthly[$monthKey])) {
                    $monthly[$monthKey]['income_cents'] += $cycleCents;
                    $entries[] = [
                        'type' => 'income',
                        'date' => $cursor->toDateString(),
                        'description' => $rec->name,
                        'amount_cents' => $cycleCents,
                        'amount' => (new Money($cycleCents, $rec->currency ?: $currency))->format($locale),
                        'source' => __('finance/projection.sources.recurring_fattura'),
                    ];
                }
                $cursor = Carbon::instance($rec->frequency->advance($cursor));
            }
        }

        // Projected loss from active DomainName renewals.
        $domainQuery = DomainName::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereNotNull('renewal_cost_cents');

        foreach ($domainQuery->get() as $domain) {
            $cost = (int) $domain->renewal_cost_cents;
            if ($cost <= 0) {
                continue;
            }

            $periodMonths = max(1, (int) $domain->renewal_period_months);
            $cursor = $domain->expires_at->copy()->startOfDay();

            while ($cursor->lte($end)) {
                $monthKey = $cursor->format('Y-m');
                if (isset($monthly[$monthKey])) {
                    $monthly[$monthKey]['loss_cents'] += $cost;
                    $entries[] = [
                        'type' => 'loss',
                        'date' => $cursor->toDateString(),
                        'description' => __('finance/projection.entries.domain_renewal', ['name' => $domain->name]),
                        'amount_cents' => $cost,
                        'amount' => (new Money($cost, $domain->currency ?: $currency))->format($locale),
                        'source' => __('finance/projection.sources.domain'),
                    ];
                }
                $cursor = $cursor->copy()->addMonths($periodMonths);
            }
        }

        // Projected loss from active Website renewals — symmetric to
        // the DomainName branch above. Websites carry a single
        // `renewal_cost_cents` and reuse the app-default currency.
        $websiteQuery = Website::query()
            ->active()
            ->whereNotNull('next_renewal_at')
            ->whereNotNull('renewal_cost_cents');

        foreach ($websiteQuery->get() as $website) {
            $cost = (int) $website->renewal_cost_cents;
            if ($cost <= 0) {
                continue;
            }

            $periodMonths = max(1, (int) $website->renewal_period_months);
            $cursor = $website->next_renewal_at->copy()->startOfDay();

            while ($cursor->lte($end)) {
                $monthKey = $cursor->format('Y-m');
                if (isset($monthly[$monthKey])) {
                    $monthly[$monthKey]['loss_cents'] += $cost;
                    $entries[] = [
                        'type' => 'loss',
                        'date' => $cursor->toDateString(),
                        'description' => __('finance/projection.entries.website_renewal', [
                            'name' => $website->getTranslation('name', $locale) ?: $website->url,
                        ]),
                        'amount_cents' => $cost,
                        'amount' => (new Money($cost, $currency))->format($locale),
                        'source' => __('finance/projection.sources.website'),
                    ];
                }
                $cursor = $cursor->copy()->addMonths($periodMonths);
            }
        }

        // Format monthly buckets + compute totals.
        $totalIncome = 0;
        $totalLoss = 0;
        $maxRowCents = 1; // avoid div-by-zero in the bar widths

        foreach ($monthly as &$row) {
            $totalIncome += $row['income_cents'];
            $totalLoss += $row['loss_cents'];
            $maxRowCents = max($maxRowCents, $row['income_cents'], $row['loss_cents']);
        }
        unset($row);

        // Second pass: render display strings + bar percentages.
        $monthlyOut = [];
        foreach ($monthly as $row) {
            $net = $row['income_cents'] - $row['loss_cents'];
            $monthlyOut[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'income' => (new Money($row['income_cents'], $currency))->format($locale),
                'loss' => (new Money($row['loss_cents'], $currency))->format($locale),
                'net' => (new Money($net, $currency))->format($locale),
                'net_negative' => $net < 0,
                'income_pct' => (int) round(($row['income_cents'] / $maxRowCents) * 100),
                'loss_pct' => (int) round(($row['loss_cents'] / $maxRowCents) * 100),
            ];
        }

        // Sort entries chronologically.
        usort($entries, fn ($a, $b) => strcmp($a['date'], $b['date']));

        $net = $totalIncome - $totalLoss;

        return [
            'window_months' => $months,
            'monthly' => $monthlyOut,
            'entries' => $entries,
            'totals' => [
                'income' => (new Money($totalIncome, $currency))->format($locale),
                'loss' => (new Money($totalLoss, $currency))->format($locale),
                'net' => (new Money($net, $currency))->format($locale),
                'net_negative' => $net < 0,
            ],
            'has_data' => $totalIncome > 0 || $totalLoss > 0,
        ];
    }

    /**
     * Total per cycle in cents for a RecurringFattura's lines array.
     * Mirrors FatturaService::computeTotals so projection math matches
     * what the actual issued fattura would total to.
     *
     * @param  array<int, array<string,mixed>>  $lines
     */
    protected function cycleTotalCents(array $lines): int
    {
        $total = 0;
        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unit = (int) ($line['unit_price_cents'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);

            $lineSubtotal = (int) round($qty * $unit);
            $lineVat = (int) round($lineSubtotal * $rate / 100);

            $total += $lineSubtotal + $lineVat;
        }

        return $total;
    }

    /**
     * For tests: lets callers project from a fixed reference date so
     * assertions don't drift with the system clock.
     */
    public function projectionFrom(CarbonInterface $reference, int $months): array
    {
        Carbon::setTestNow($reference);
        $this->form->fill(['months' => $months]);
        $data = $this->getViewData();
        Carbon::setTestNow();

        return $data;
    }
}
