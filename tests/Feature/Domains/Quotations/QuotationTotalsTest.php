<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages\EditQuotation;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Quotations\Services\Public\QuotationsService;
use App\Models\User;
use Livewire\Livewire;

it('re-derives stored totals when lines are edited through the Filament edit page', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();
    // The Contact 360 quick action creates exactly this: a quotation with
    // no lines, which the operator then fills in on the edit page.
    $quotation = app(QuotationsService::class)->create([
        'name' => 'Restyling',
        'client_contact_id' => $contact->id,
        'lines' => [],
    ]);
    expect($quotation->total_cents)->toBe(0);

    Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->fillForm([
            'lines' => [[
                'description' => 'Restyling homepage',
                'qty' => 1,
                'unit_price_cents' => '2200.00',
                'vat_rate' => 22,
                'cadence' => 'una_tantum',
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $quotation->fresh();
    expect($fresh->subtotal_cents)->toBe(220_000);
    expect($fresh->vat_cents)->toBe(48_400);
    expect($fresh->total_cents)->toBe(268_400);
});

it('derives totals from lines on create, whatever the caller passed', function () {
    $quotation = Quotation::factory()->create([
        'lines' => [['description' => 'X', 'qty' => 2, 'unit_price_cents' => 5_000, 'vat_rate' => 10]],
        'total_cents' => 1, // ignored — totals are a function of the lines
    ]);

    expect($quotation->fresh()->total_cents)->toBe(11_000);
});
