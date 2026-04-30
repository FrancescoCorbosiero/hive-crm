<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\Documents\Events\PaymentRecorded;
use App\Domains\Documents\Models\Payment;
use App\Domains\Finance\Services\Public\FinanceService;

/**
 * When a payment is recorded in the Documents domain, mirror it as an
 * Income transaction so the ledger reflects cash actually received.
 *
 * Payment is the source of truth; we store the resulting transaction
 * id back on the payment row so it can be cleaned up if the payment
 * is deleted (the matching deletion listener does that round trip).
 *
 * Cross-domain wiring: this listener imports the Documents Payment
 * model. Per the architectural rules events are explicit cross-domain
 * primitives — Mail's bounce listener does the same with
 * ContactFlaggedDoNotEmail. We deliberately don't reach for a
 * "PaymentDTO" here because mirroring is an internal concern of
 * Finance, not part of any public surface.
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

        // Already mirrored — keeps the listener idempotent in the face
        // of duplicate event delivery.
        if ($payment->transaction_id !== null) {
            return;
        }

        $fattura = $payment->fattura;
        if (! $fattura) {
            return;
        }

        $transactionId = $this->finance->recordIncome([
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

        $payment->update(['transaction_id' => $transactionId]);
    }
}
