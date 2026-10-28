<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Database\Seeders\DocumentsSeeder;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use App\Domains\Documents\Services\Public\FatturaService;

beforeEach(function () {
    Contact::factory()->create(['email' => 'amministrazione@bellavistadolci.it']);
});

it('never touches a ledger that already holds real fatture', function () {
    $real = app(FatturaService::class)->create([
        'client_contact_id' => Contact::factory()->create()->id,
        'lines' => [['description' => 'Fattura vera', 'qty' => 1, 'unit_price_cents' => 99_900, 'vat_rate' => 22]],
    ]);

    (new DocumentsSeeder)->run();

    expect(Fattura::count())->toBe(1);
    expect($real->fresh()->lines[0]['description'])->toBe('Fattura vera');
    expect(FatturaCounter::query()->find($real->year)->last_number)->toBe(1);
});

it('seeds two numbered demo fatture into an empty ledger, once', function () {
    (new DocumentsSeeder)->run();
    (new DocumentsSeeder)->run();

    $fatture = Fattura::query()->orderBy('number')->get();
    expect($fatture)->toHaveCount(2);
    expect($fatture->pluck('number')->all())->toBe([1, 2]);
    expect($fatture[0]->payment_status)->toBe(PaymentStatus::Paid);
    expect($fatture[1]->total_cents)->toBe(305_000);
    expect(FatturaCounter::query()->find($fatture[0]->year)->last_number)->toBe(2);
});
