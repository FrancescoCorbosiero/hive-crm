<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Public;

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Events\FatturaPaid;
use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\Payment;
use App\Shared\ValueObjects\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentsService
{
    /**
     * Record a payment against a fattura. Re-aggregates paid_amount_cents
     * and re-derives payment_status atomically so the cached roll-up on
     * the fattura row is always consistent with the payments table.
     *
     * Dispatches PaymentRecorded (always) and FatturaPaid (only on the
     * unpaid|partially_paid|overdue → paid transition).
     *
     * @param  array{
     *     paid_at?: \DateTimeInterface|string|null,
     *     amount_cents: int,
     *     currency?: string,
     *     method?: string,
     *     reference?: ?string,
     *     notes?: ?string,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function record(int $fatturaId, array $attributes): Payment
    {
        return DB::transaction(function () use ($fatturaId, $attributes) {
            $fattura = Fattura::query()->lockForUpdate()->findOrFail($fatturaId);
            $previousStatus = $fattura->payment_status;

            $payment = Payment::query()->create([
                'fattura_id' => $fattura->id,
                'paid_at' => isset($attributes['paid_at'])
                    ? Carbon::parse($attributes['paid_at'])
                    : Carbon::now(),
                'amount_cents' => (int) $attributes['amount_cents'],
                'currency' => $attributes['currency'] ?? $fattura->currency,
                'method' => $attributes['method'] ?? PaymentMethod::BankTransfer->value,
                'reference' => $attributes['reference'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'owner_user_id' => $attributes['owner_user_id'] ?? $fattura->owner_user_id,
            ]);

            $this->recomputeFromPayments($fattura);

            PaymentRecorded::dispatch($payment->id);

            if ($previousStatus !== PaymentStatus::Paid && $fattura->fresh()->payment_status === PaymentStatus::Paid) {
                FatturaPaid::dispatch($fattura->id, (int) $fattura->client_contact_id);
            }

            return $payment;
        });
    }

    /**
     * Delete a payment and re-aggregate. Status may move backwards
     * (paid → partially_paid → unpaid) when this happens.
     */
    public function delete(int $paymentId): void
    {
        DB::transaction(function () use ($paymentId) {
            $payment = Payment::query()->findOrFail($paymentId);
            $fatturaId = $payment->fattura_id;
            $payment->delete();

            $fattura = Fattura::query()->lockForUpdate()->find($fatturaId);
            if ($fattura) {
                $this->recomputeFromPayments($fattura);
            }
        });
    }

    public function outstandingAmount(int $fatturaId): Money
    {
        $fattura = Fattura::query()->findOrFail($fatturaId);
        $cents = max(0, (int) $fattura->total_cents - (int) $fattura->paid_amount_cents);

        return new Money($cents, $fattura->currency);
    }

    /**
     * @return Collection<int, Payment>
     */
    public function paymentsFor(int $fatturaId): Collection
    {
        return Payment::query()
            ->where('fattura_id', $fatturaId)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Recompute paid_amount_cents + payment_status from the underlying
     * payments rows. Single source of truth — the cached columns can
     * be regenerated from this method at any time.
     *
     * Status precedence: Cancelled > Paid > Overdue (when past due_date)
     * > Partially Paid > Unpaid. Cancelled is sticky — once a fattura
     * is voided, no payment recompute revives it.
     */
    public function recomputeFromPayments(Fattura $fattura): void
    {
        if ($fattura->payment_status === PaymentStatus::Cancelled) {
            return;
        }

        $paid = (int) Payment::query()
            ->where('fattura_id', $fattura->id)
            ->sum('amount_cents');

        $newStatus = match (true) {
            $paid >= $fattura->total_cents && $fattura->total_cents > 0 => PaymentStatus::Paid,
            $paid > 0 => PaymentStatus::PartiallyPaid,
            $fattura->due_date && Carbon::parse($fattura->due_date)->isPast() => PaymentStatus::Overdue,
            default => PaymentStatus::Unpaid,
        };

        $fattura->update([
            'paid_amount_cents' => $paid,
            'payment_status' => $newStatus->value,
        ]);
    }
}
