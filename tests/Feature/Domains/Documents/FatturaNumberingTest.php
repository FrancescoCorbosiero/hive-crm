<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use App\Domains\Documents\Services\Public\FatturaService;

function fatturaPayload(int $clientId, ?int $year = null): array
{
    return [
        'client_contact_id' => $clientId,
        'year' => $year ?? now()->year,
        'issued_at' => now(),
        'lines' => [
            ['description' => 'Servizi vari', 'qty' => 1, 'unit_price_cents' => 100_000, 'vat_rate' => 22],
        ],
    ];
}

it('allocates 1, 2, 3, … in sequence within a year', function () {
    $contact = Contact::factory()->create();
    $svc = app(FatturaService::class);
    $year = now()->year;

    $a = $svc->create(fatturaPayload($contact->id, $year));
    $b = $svc->create(fatturaPayload($contact->id, $year));
    $c = $svc->create(fatturaPayload($contact->id, $year));

    expect([$a->number, $b->number, $c->number])->toBe([1, 2, 3]);
});

it('keeps numbering separate across years', function () {
    $contact = Contact::factory()->create();
    $svc = app(FatturaService::class);

    $f24 = $svc->create(fatturaPayload($contact->id, 2024));
    $f25 = $svc->create(fatturaPayload($contact->id, 2025));
    $f24b = $svc->create(fatturaPayload($contact->id, 2024));

    expect($f24->number)->toBe(1);
    expect($f25->number)->toBe(1);
    expect($f24b->number)->toBe(2);
});

it('produces 50 unique sequential numbers across rapid sequential calls', function () {
    $contact = Contact::factory()->create();
    $svc = app(FatturaService::class);
    $year = now()->year;

    $numbers = [];
    for ($i = 0; $i < 50; $i++) {
        $numbers[] = $svc->create(fatturaPayload($contact->id, $year))->number;
    }

    expect($numbers)->toBe(range(1, 50));
    expect(Fattura::where('year', $year)->count())->toBe(50);
});

it('the (year, number) UNIQUE constraint is the second line of defence', function () {
    $contact = Contact::factory()->create();
    Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'year' => 2030,
        'number' => 1,
    ]);

    expect(fn () => Fattura::factory()->create([
        'client_contact_id' => $contact->id,
        'year' => 2030,
        'number' => 1,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('persists a counter row per year and never lets it move backwards', function () {
    $contact = Contact::factory()->create();
    $svc = app(FatturaService::class);
    $year = now()->year;

    $svc->create(fatturaPayload($contact->id, $year));
    $svc->create(fatturaPayload($contact->id, $year));

    $counter = FatturaCounter::query()->find($year);
    expect($counter->last_number)->toBe(2);

    $svc->create(fatturaPayload($contact->id, $year));

    $counter->refresh();
    expect($counter->last_number)->toBe(3);
});

it('fills gaps by always allocating max+1, even after a manual delete', function () {
    $contact = Contact::factory()->create();
    $svc = app(FatturaService::class);
    $year = now()->year;

    $a = $svc->create(fatturaPayload($contact->id, $year));
    $b = $svc->create(fatturaPayload($contact->id, $year));
    $b->delete(); // simulate an admin deleting #2

    $c = $svc->create(fatturaPayload($contact->id, $year));

    // Numbering must NOT reuse #2 — counter is monotonic regardless of
    // whether rows still exist. This is the canonical Italian-fattura
    // requirement.
    expect($c->number)->toBe(3);
});
