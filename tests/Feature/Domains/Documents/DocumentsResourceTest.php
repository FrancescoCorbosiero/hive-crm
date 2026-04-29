<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages\CreateFattura;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages\EditFattura;
use App\Domains\Documents\Filament\Resources\FatturaResource\Pages\ListFatture;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\Fattura;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    // Fake the s3 disk so the table action's temporaryUrl() closure
    // doesn't try to reach the real AWS metadata service.
    Storage::fake('s3');
});

it('renders the documents index page', function () {
    Document::factory()->count(2)->create();
    Livewire::test(ListDocuments::class)->assertSuccessful();
});

it('renders the fatture index page', function () {
    $contact = Contact::factory()->create();
    Fattura::factory()->create(['client_contact_id' => $contact->id]);
    Livewire::test(ListFatture::class)->assertSuccessful();
});

it('renders the fattura create page', function () {
    Livewire::test(CreateFattura::class)->assertSuccessful();
});

it('renders the fattura edit page', function () {
    $contact = Contact::factory()->create();
    $fattura = Fattura::factory()->create(['client_contact_id' => $contact->id]);
    Livewire::test(EditFattura::class, ['record' => $fattura->id])->assertSuccessful();
});
