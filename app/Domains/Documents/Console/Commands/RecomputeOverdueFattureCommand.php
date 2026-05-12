<?php

declare(strict_types=1);

namespace App\Domains\Documents\Console\Commands;

use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\PaymentsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sweep every non-final fattura whose due_date has passed and let
 * PaymentsService re-derive the payment_status. Without this, an
 * invoice that nobody touches silently stays "unpaid" past its
 * due_date — the Overdue transition only happens when a payment is
 * recorded or deleted.
 */
class RecomputeOverdueFattureCommand extends Command
{
    protected $signature = 'fatture:recompute-overdue';

    protected $description = 'Recompute payment_status for unpaid/partially-paid fatture past due_date.';

    public function handle(PaymentsService $service): int
    {
        $today = now()->toDateString();
        $recomputed = 0;
        $failures = 0;

        Fattura::query()
            ->whereIn('payment_status', [
                PaymentStatus::Unpaid->value,
                PaymentStatus::PartiallyPaid->value,
                PaymentStatus::Overdue->value,
            ])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->each(function (Fattura $fattura) use ($service, &$recomputed, &$failures) {
                try {
                    $service->recomputeFromPayments($fattura);
                    $recomputed++;
                } catch (\Throwable $e) {
                    Log::error('fatture.overdue.recompute_failed', [
                        'fattura_id' => $fattura->id,
                        'exception' => $e->getMessage(),
                    ]);
                    $failures++;
                }
            });

        $this->info("Recomputed {$recomputed} fattura(s); {$failures} failure(s).");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
