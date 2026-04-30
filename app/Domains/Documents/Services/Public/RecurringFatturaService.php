<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Public;

use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\RecurringFattura;
use Carbon\Carbon;
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
                'next_issue_at' => $this->advanceNextIssueAt($rec),
            ]);

            return $fattura;
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
     * Advance next_issue_at by one period of the schedule's frequency.
     * For monthly schedules with a day_of_month set, lands on that day
     * of the *target* month, clamped to the month's length so
     * Jan 31 + 1mo → Feb 28 rather than overflowing into March via
     * Carbon's default addMonth() behaviour.
     */
    private function advanceNextIssueAt(RecurringFattura $rec): Carbon
    {
        if ($rec->frequency === RecurringFrequency::Monthly && $rec->day_of_month) {
            // Snap to first-of-month before adding so addMonth() can't
            // overflow on a day that doesn't exist in the next month.
            $base = $rec->next_issue_at->copy()->startOfMonth()->addMonth();
            $clamped = min($rec->day_of_month, $base->daysInMonth);
            return $base->day($clamped);
        }

        return $rec->frequency->advance($rec->next_issue_at);
    }
}
