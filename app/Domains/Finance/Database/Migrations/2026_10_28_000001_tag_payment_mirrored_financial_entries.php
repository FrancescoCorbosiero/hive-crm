<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RecordIncomeFromPayment now tags the INCOME entry it mirrors from a
 * payment with external_ref = "payment:{paymentId}", which is how the
 * entry is found again (and retracted) when the payment is deleted.
 *
 * Backfill the tag on entries mirrored before that. Every existing
 * payments.financial_entry_id was written by that listener, so the
 * linked entries are all mirrors. Data-only and idempotent: untagged
 * rows only, nothing to undo on rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->whereNotNull('financial_entry_id')
            ->orderBy('id')
            ->select(['id', 'financial_entry_id'])
            ->each(function (object $payment): void {
                DB::table('financial_entries')
                    ->where('id', $payment->financial_entry_id)
                    ->whereNull('external_ref')
                    ->update(['external_ref' => 'payment:'.$payment->id]);
            });
    }

    public function down(): void
    {
        //
    }
};
