<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Filament\Resources\RecurringFatturaResource;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Finance\Filament\Resources\RecurringExpenseResource;
use App\Domains\Finance\Models\RecurringExpense;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Filament\Resources\WebsiteResource;
use App\Domains\Websites\Models\Website;
use App\Shared\ValueObjects\Money;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Active Subscriptions — unified view across three sources:
 *
 *   - Websites             (revenue, subscription-billed sites)
 *   - RecurringFattura     (revenue, recurring invoices to customers)
 *   - RecurringExpense     (cost,    your own SaaS / hosting / tools)
 *
 * Rows are normalized to a common shape and flagged as `delayed`
 * when the next due date is in the past, so the same widget covers
 * "subscription with eventual delays highlighted" for both sides
 * of the ledger.
 */
class ActiveSubscriptionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.active-subscriptions';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.active_subscriptions.heading');
    }

    /**
     * @return Collection<int, array{
     *     id: string,
     *     kind: string,
     *     direction: 'income'|'loss',
     *     name: string,
     *     counterparty: ?string,
     *     amount: ?string,
     *     frequency: string,
     *     started_at: ?string,
     *     next_due_at: ?string,
     *     delayed: bool,
     *     edit_url: string,
     * }>
     */
    public function getRowsProperty(): Collection
    {
        $locale = app()->getLocale();
        $today = now()->startOfDay();

        $websites = Website::query()
            ->where('status', WebsiteStatus::Active->value)
            ->whereNotNull('next_renewal_at')
            ->get()
            ->map(function (Website $w) use ($locale, $today) {
                $period = (int) ($w->renewal_period_months ?: 12);

                return [
                    'id' => 'website-'.$w->id,
                    'kind' => __('dashboard.active_subscriptions.kinds.website'),
                    'direction' => 'income',
                    'name' => $w->getTranslation('name', $locale),
                    'counterparty' => $this->resolveContactName($w->owner_contact_id),
                    'amount' => null,
                    'frequency' => $this->labelForMonths($period),
                    'started_at' => $w->subscription_started_at?->format('d/m/Y'),
                    'next_due_at' => $w->next_renewal_at?->format('d/m/Y'),
                    'delayed' => $w->next_renewal_at !== null && $w->next_renewal_at->lt($today),
                    'edit_url' => WebsiteResource::getUrl('edit', ['record' => $w]),
                ];
            });

        $recurringRevenue = RecurringFattura::query()
            ->where('is_active', true)
            ->get()
            ->map(function (RecurringFattura $r) use ($today) {
                return [
                    'id' => 'rfattura-'.$r->id,
                    'kind' => __('dashboard.active_subscriptions.kinds.recurring_fattura'),
                    'direction' => 'income',
                    'name' => $r->name,
                    'counterparty' => $this->resolveContactName($r->client_contact_id),
                    'amount' => $this->formatRecurringFatturaTotal($r),
                    'frequency' => $r->frequency->label(),
                    'started_at' => null,
                    'next_due_at' => $r->next_issue_at?->format('d/m/Y'),
                    'delayed' => $r->next_issue_at !== null && $r->next_issue_at->lt($today),
                    'edit_url' => RecurringFatturaResource::getUrl('edit', ['record' => $r]),
                ];
            });

        $recurringCost = RecurringExpense::query()
            ->where('is_active', true)
            ->get()
            ->map(function (RecurringExpense $e) use ($locale, $today) {
                return [
                    'id' => 'rexpense-'.$e->id,
                    'kind' => __('dashboard.active_subscriptions.kinds.recurring_expense'),
                    'direction' => 'loss',
                    'name' => $e->name,
                    'counterparty' => $this->resolveContactName($e->vendor_contact_id),
                    'amount' => $e->money()->format($locale),
                    'frequency' => $e->frequency->label(),
                    'started_at' => $e->started_at?->format('d/m/Y'),
                    'next_due_at' => $e->next_due_at?->format('d/m/Y'),
                    'delayed' => $e->next_due_at !== null && $e->next_due_at->lt($today),
                    'edit_url' => RecurringExpenseResource::getUrl('edit', ['record' => $e]),
                ];
            });

        return $websites
            ->concat($recurringRevenue)
            ->concat($recurringCost)
            ->sortBy([
                // Delayed first, then by next-due ascending (nulls last).
                fn ($a, $b) => ($b['delayed'] <=> $a['delayed']),
                fn ($a, $b) => $this->compareDates($a['next_due_at'], $b['next_due_at']),
            ])
            ->values();
    }

    private function resolveContactName(?int $contactId): ?string
    {
        if (! $contactId) {
            return null;
        }

        return Contact::query()->whereKey($contactId)->value('name');
    }

    private function labelForMonths(int $months): string
    {
        return match ($months) {
            1 => __('documents/labels.frequency.monthly'),
            3 => __('documents/labels.frequency.quarterly'),
            12 => __('documents/labels.frequency.yearly'),
            default => __('dashboard.active_subscriptions.every_n_months', ['n' => $months]),
        };
    }

    private function formatRecurringFatturaTotal(RecurringFattura $r): ?string
    {
        $lines = $r->lines?->toArray() ?? [];
        if ($lines === []) {
            return null;
        }

        $cents = 0;
        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 1);
            $unit = (int) ($line['unit_price_cents'] ?? 0);
            $vatRate = (float) ($line['vat_rate'] ?? 0);
            $net = (int) round($qty * $unit);
            $cents += $net + (int) round($net * $vatRate / 100);
        }

        return (new Money($cents, $r->currency ?: config('app.currency', 'EUR')))
            ->format(app()->getLocale());
    }

    private function compareDates(?string $a, ?string $b): int
    {
        if ($a === null && $b === null) {
            return 0;
        }
        if ($a === null) {
            return 1;
        }
        if ($b === null) {
            return -1;
        }

        return Carbon::createFromFormat('d/m/Y', $a)
            <=> Carbon::createFromFormat('d/m/Y', $b);
    }
}
