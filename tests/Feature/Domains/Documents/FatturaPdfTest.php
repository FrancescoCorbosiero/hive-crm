<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Services\Public\FatturaService;
use Illuminate\Support\Facades\Storage;

it('renders a PDF, uploads it to the configured disk, and links a Document row', function () {
    Storage::fake('s3');
    $contact = Contact::factory()->create([
        'name' => 'Pasticceria Test',
        'address' => ['street' => 'Via Roma 1', 'city' => 'Milano', 'province' => 'MI', 'postal_code' => '20121', 'country' => 'IT'],
        'vat_number' => 'IT12345678901',
    ]);

    $svc = app(FatturaService::class);
    $fattura = $svc->create([
        'client_contact_id' => $contact->id,
        'lines' => [
            ['description' => 'Servizio test con € e accenti àèìòù', 'qty' => 1, 'unit_price_cents' => 12345, 'vat_rate' => 22],
        ],
    ]);

    $documentId = $svc->render($fattura->id, disk: 's3');

    expect($documentId)->toBeInt();
    $document = Document::find($documentId);
    expect($document->category)->toBe(DocumentCategory::Fattura);
    expect($document->mime)->toBe('application/pdf');
    expect($document->related_type)->toBe('fattura');
    expect($document->related_id)->toBe($fattura->id);

    Storage::disk('s3')->assertExists($document->file_path);

    // Sanity: the stored file is a real PDF (starts with %PDF-).
    $bytes = Storage::disk('s3')->get($document->file_path);
    expect(substr($bytes, 0, 5))->toBe('%PDF-');
    expect(strlen($bytes))->toBeGreaterThan(1000);

    // Linkback updated on the Fattura.
    expect($fattura->fresh()->document_id)->toBe($documentId);
});

it('exposes a payload DTO carrying client + line breakdown for downstream exporters', function () {
    $contact = Contact::factory()->create();

    $svc = app(FatturaService::class);
    $fattura = $svc->create([
        'client_contact_id' => $contact->id,
        'lines' => [
            ['description' => 'Linea X', 'qty' => 2, 'unit_price_cents' => 10000, 'vat_rate' => 22],
        ],
    ]);

    $payload = $svc->payload($fattura->id);

    expect($payload->id)->toBe($fattura->id);
    expect($payload->client?->id)->toBe($contact->id);
    expect($payload->lines)->toHaveCount(1);
    expect($payload->lines[0]->lineSubtotal()->cents)->toBe(20000);
    expect($payload->subtotal->cents)->toBe(20000);
    expect($payload->total->cents)->toBe(24400);
    expect($payload->displayNumber)->toMatch('/^\d{4}\/\d{4}$/');
});
