<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\Documents\Models\Payment;
use App\Domains\Finance\Services\Public\FinanceService;

/**
 * When a payment is recorded in the Documents domain, mirror it as an
 * Income FinancialEntry so the ledger reflects cash actually received.
 *
 * Payment is the source of truth here. The entry carries
 * external_ref = "payment:{paymentId}" — a DB-unique idempotency key and
 * the handle RetractIncomeForDeletedPayment uses when the payment goes
 * away — and its id is stored back on the payment row.
 *
 * A payment that arrives already linked to an entry (a fattura generated
 * from an existing income entry, see FinanceService::generateFattura)
 * is settled against that entry: nothing new is booked.
 */
class RecordIncomeFromPayment
{
    public function __construct(private readonly FinanceService $finance) {}

    public function handle(PaymentRecorded $event): void
    {
        $payment = Payment::query()->find($event->paymentId);
        if (! $payment) {
            return;
        }

        // Already mirrored (or settled against an existing entry) —
        // keeps the listener idempotent under duplicate event delivery.
        $externalRef = self::externalRef($payment->id);
        if ($payment->financial_entry_id !== null || $this->finance->findIdByExternalRef($externalRef) !== null) {
            return;
        }

        $fattura = $payment->fattura;
        if (! $fattura) {
            return;
        }

        $entryId = $this->finance->recordIncome([
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'occurred_at' => $payment->paid_at,
            'description' => 'Pagamento '.$fattura->displayNumber(),
            'category' => 'website_subscription',
            'source_type' => 'fattura',
            'source_id' => $fattura->id,
            'contact_id' => $fattura->client_contact_id,
            'external_ref' => $externalRef,
            'owner_user_id' => $payment->owner_user_id,
        ]);

        $payment->update(['financial_entry_id' => $entryId]);
    }

    public static function externalRef(int $paymentId): string
    {
        return 'payment:'.$paymentId;
    }
}
