<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages\CreateQuotation;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages\EditQuotation;
use App\Domains\Quotations\Filament\Resources\QuotationResource\Pages\ListQuotations;
use App\Domains\Quotations\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('s3');
});

it('renders the quotations index page', function () {
    $contact = Contact::factory()->create();
    Quotation::factory()->create(['client_contact_id' => $contact->id]);
    Livewire::test(ListQuotations::class)->assertSuccessful();
});

it('renders the quotation create page', function () {
    Livewire::test(CreateQuotation::class)->assertSuccessful();
});

it('renders the quotation edit page', function () {
    $contact = Contact::factory()->create();
    $q = Quotation::factory()->create(['client_contact_id' => $contact->id]);
    Livewire::test(EditQuotation::class, ['record' => $q->id])->assertSuccessful();
});
