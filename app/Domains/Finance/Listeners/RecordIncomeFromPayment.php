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
 * Payment is the source of truth here; the resulting financial_entry id
 * is stored on the payment row so it can be cleaned up if the payment
 * is deleted.
 *
 * Note: the reverse direction (FinancialEntry → Fattura, on demand)
 * lives on FinanceService::generateFattura(). This listener handles
 * the legacy direction only — fatture that pre-date the entry-first
 * workflow.
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

        // Already mirrored — keeps the listener idempotent under
        // duplicate event delivery.
        if ($payment->financial_entry_id !== null) {
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
            'owner_user_id' => $payment->owner_user_id,
        ]);

        $payment->update(['financial_entry_id' => $entryId]);
    }
}
