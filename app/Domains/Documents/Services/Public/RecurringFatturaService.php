<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Public;

use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\RecurringFattura;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecurringFatturaService
{
    public function __construct(private readonly FatturaService $fatture) {}

    /**
     * @param  array{
     *     name: string,
     *     client_contact_id: int,
     *     frequency: string,
     *     lines: array<int, array<string,mixed>>,
     *     currency?: string,
     *     day_of_month?: ?int,
     *     next_issue_at?: \DateTimeInterface|string|null,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function create(array $attributes): RecurringFattura
    {
        return RecurringFattura::query()->create([
            'name' => $attributes['name'],
            'client_contact_id' => $attributes['client_contact_id'],
            'frequency' => $attributes['frequency'],
            'lines' => $attributes['lines'],
            'currency' => $attributes['currency'] ?? config('app.currency', 'EUR'),
            'day_of_month' => $attributes['day_of_month'] ?? null,
            'next_issue_at' => $attributes['next_issue_at'] ?? Carbon::now(),
            'is_active' => true,
            'owner_user_id' => $attributes['owner_user_id'] ?? null,
        ]);
    }

    /**
     * Issue one fattura from this recurring schedule and advance
     * next_issue_at by one period of the configured frequency.
     *
     * Wraps both operations in a single transaction so a fattura
     * generation failure won't silently advance the schedule (we'd
     * skip a billing cycle without anyone noticing).
     */
    public function issue(int $recurringId): Fattura
    {
        return DB::transaction(function () use ($recurringId) {
            $rec = RecurringFattura::query()->lockForUpdate()->findOrFail($recurringId);

            $fattura = $this->fatture->create([
                'client_contact_id' => $rec->client_contact_id,
                'issued_at' => Carbon::now(),
                'lines' => (array) $rec->lines,
                'currency' => $rec->currency,
                'owner_user_id' => $rec->owner_user_id,
            ]);

            $rec->update([
                'last_issued_at' => Carbon::now(),
                'next_issue_at' => $this->advanceFrom($rec, $rec->next_issue_at),
            ]);

            return $fattura;
        });
    }

    /**
     * Backfill missing past invoices for this recurring schedule.
     * Issues one fattura per cycle from $from (inclusive) up to but
     * NOT INCLUDING the schedule's currently-configured `next_issue_at`,
     * each backdated to the cycle's date. `next_issue_at` is left
     * untouched so the forward schedule keeps running as configured;
     * `last_issued_at` is updated to the most recent generated date.
     *
     * Returns the number of fatture generated. The whole loop is one
     * transaction — partial backfills don't leave the counter in a
     * weird state.
     *
     * NOTE: fattura numbers are allocated sequentially per fiscal year
     * in the order they are CREATED, not in the order they are dated.
     * Backfill should therefore run BEFORE issuing any current-period
     * fatture for the same year, or numbering will be out of order
     * relative to the dates.
     */
    public function backfill(int $recurringId, \DateTimeInterface|string $from): int
    {
        return DB::transaction(function () use ($recurringId, $from) {
            $rec = RecurringFattura::query()->lockForUpdate()->findOrFail($recurringId);

            $stop = $rec->next_issue_at?->copy()->startOfDay()
                ?? Carbon::now()->startOfDay();

            $cursor = $this->snapToSchedule($rec, Carbon::parse($from)->startOfDay());

            $generated = 0;
            $lastIssued = null;

            while ($cursor->lt($stop)) {
                $this->fatture->create([
                    'client_contact_id' => $rec->client_contact_id,
                    'issued_at' => $cursor->toDateString(),
                    'lines' => (array) $rec->lines,
                    'currency' => $rec->currency,
                    'owner_user_id' => $rec->owner_user_id,
                ]);

                $generated++;
                $lastIssued = $cursor->copy();
                $cursor = $this->advanceFrom($rec, $cursor);
            }

            if ($lastIssued !== null) {
                $rec->update(['last_issued_at' => $lastIssued]);
            }

            return $generated;
        });
    }

    public function pause(int $id): void
    {
        RecurringFattura::query()->where('id', $id)->update(['is_active' => false]);
    }

    public function resume(int $id): void
    {
        RecurringFattura::query()->where('id', $id)->update(['is_active' => true]);
    }

    /**
     * Advance a date by one period of the schedule's frequency.
     * For monthly schedules with a day_of_month set, lands on that
     * day of the *target* month, clamped to the month's length so
     * Jan 31 + 1mo → Feb 28 rather than overflowing into March via
     * Carbon's default addMonth() behaviour.
     */
    private function advanceFrom(RecurringFattura $rec, CarbonInterface $from): Carbon
    {
        if ($rec->frequency === RecurringFrequency::Monthly && $rec->day_of_month) {
            $base = Carbon::instance($from)->copy()->startOfMonth()->addMonth();
            $clamped = min($rec->day_of_month, $base->daysInMonth);

            return $base->day($clamped);
        }

        return Carbon::instance($rec->frequency->advance($from));
    }

    /**
     * Snap a starting date onto the schedule's day_of_month (for
     * monthly schedules). For other frequencies the date is returned
     * unchanged — the caller's chosen "from" date IS the schedule
     * anchor for that backfill run.
     */
    private function snapToSchedule(RecurringFattura $rec, Carbon $date): Carbon
    {
        if ($rec->frequency === RecurringFrequency::Monthly && $rec->day_of_month) {
            $clamped = min($rec->day_of_month, $date->daysInMonth);

            return $date->copy()->day($clamped);
        }

        return $date;
    }
}
