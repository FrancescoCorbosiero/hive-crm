<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\Payment;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Finance\Services\Public\FinanceService;

/**
 * Payments are mirrored into the Finance ledger as INCOME entries. The
 * ledger must stay in step when payments or fatture go away, and must
 * never count the same cash twice.
 */
function paidFattura(int $cents = 100_000): Fattura
{
    $fattura = Fattura::factory()->totalling($cents)->create(['client_contact_id' => Contact::factory()->create()->id]);
    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => $cents]);

    return $fattura->fresh();
}

it('tags the mirrored income entry with a stable per-payment reference', function () {
    $fattura = paidFattura();
    $payment = Payment::query()->where('fattura_id', $fattura->id)->sole();

    $entry = FinancialEntry::query()->findOrFail($payment->financial_entry_id);
    expect($entry->external_ref)->toBe('payment:'.$payment->id);
});

it('retracts the mirrored income entry when a payment is deleted', function () {
    $fattura = paidFattura();
    $payment = Payment::query()->where('fattura_id', $fattura->id)->sole();
    expect(FinancialEntry::count())->toBe(1);

    app(PaymentsService::class)->delete($payment->id);

    expect(FinancialEntry::count())->toBe(0);
    expect($fattura->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('retracts the mirrored income of every payment when the fattura itself is deleted', function () {
    $fattura = Fattura::factory()->totalling(100_000)->create(['client_contact_id' => Contact::factory()->create()->id]);
    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 40_000]);
    app(PaymentsService::class)->record($fattura->id, ['amount_cents' => 60_000]);
    $unrelated = FinancialEntry::factory()->income(5_000)->create();
    expect(FinancialEntry::count())->toBe(3);

    // What Filament's DeleteAction / DeleteBulkAction do.
    $fattura->delete();

    expect(Payment::count())->toBe(0);
    expect(FinancialEntry::query()->pluck('id')->all())->toBe([$unrelated->id]);
});

it('leaves an income entry it did not create alone when its settling payment is deleted', function () {
    $entry = FinancialEntry::factory()->income(50_000)->create(['contact_id' => Contact::factory()->create()->id]);
    $payload = app(FinanceService::class)->generateFattura($entry->id);
    $settling = Payment::query()->where('fattura_id', $payload->id)->sole();

    app(PaymentsService::class)->delete($settling->id);

    expect(FinancialEntry::query()->find($entry->id))->not->toBeNull();
});

it('detaches ledger entries that point at a deleted fattura', function () {
    $entry = FinancialEntry::factory()->income(50_000)->create(['contact_id' => Contact::factory()->create()->id]);
    $payload = app(FinanceService::class)->generateFattura($entry->id);

    Fattura::query()->findOrFail($payload->id)->delete();

    $entry->refresh();
    expect($entry->type)->toBe(FinancialEntryType::Income);
    expect($entry->amount_cents)->toBe(50_000);
    expect([$entry->source_type, $entry->source_id])->toBe([null, null]);
});

it('backfills the per-payment reference on entries mirrored before it existed', function () {
    $fattura = paidFattura();
    $payment = Payment::query()->where('fattura_id', $fattura->id)->sole();
    FinancialEntry::query()->whereKey($payment->financial_entry_id)->update(['external_ref' => null]);

    $migration = require base_path('app/Domains/Finance/Database/Migrations/2026_10_28_000001_tag_payment_mirrored_financial_entries.php');
    $migration->up();

    expect(FinancialEntry::query()->find($payment->financial_entry_id)->external_ref)->toBe('payment:'.$payment->id);

    // …so deleting that legacy payment now retracts its income too.
    app(PaymentsService::class)->delete($payment->id);
    expect(FinancialEntry::count())->toBe(0);
});
