<?php

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use App\Domains\Documents\Services\Internal\FatturaPaExporter;
use App\Domains\Documents\Services\Internal\FatturaPaImporter;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Domains\Settings\Services\Public\BusinessProfileService;

/**
 * FatturaPA import: outbound XMLs (we are the Cedente) become Fattura
 * rows, inbound ones (we are the Cessionario) become LOSS entries.
 * Owner identity comes from the business profile.
 */
beforeEach(function () {
    app(BusinessProfileService::class)->singleton()->update([
        'denominazione' => 'Hive Web Studio',
        'codice_fiscale' => 'RSSMRA80A01H501U',
        'partita_iva' => '12345678901',
        'sede_indirizzo' => 'Via Roma',
        'sede_cap' => '00100',
        'sede_comune' => 'Roma',
        'sede_provincia' => 'RM',
    ]);
});

/**
 * Minimal FPR12 document, with the `ns2:` prefix real SdI files carry.
 *
 * @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $lines  [description, qty, unit, rate]
 */
function fatturaPaXml(string $cedentePiva, string $cessionarioPiva, string $numero, string $data, array $lines, string $total): string
{
    $party = fn (string $piva, string $name) => '<DatiAnagrafici><IdFiscaleIVA><IdPaese>IT</IdPaese><IdCodice>'.$piva.'</IdCodice></IdFiscaleIVA>'
        .'<Anagrafica><Denominazione>'.$name.'</Denominazione></Anagrafica></DatiAnagrafici>'
        .'<Sede><Indirizzo>Via Verdi</Indirizzo><NumeroCivico>3</NumeroCivico><CAP>20100</CAP><Comune>Milano</Comune><Provincia>MI</Provincia><Nazione>IT</Nazione></Sede>';

    $body = '';
    foreach ($lines as $i => [$description, $qty, $unit, $rate]) {
        $body .= '<DettaglioLinee><NumeroLinea>'.($i + 1).'</NumeroLinea><Descrizione>'.$description.'</Descrizione>'
            .'<Quantita>'.$qty.'</Quantita><PrezzoUnitario>'.$unit.'</PrezzoUnitario><AliquotaIVA>'.$rate.'</AliquotaIVA></DettaglioLinee>';
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<ns2:FatturaElettronica versione="FPR12" xmlns:ns2="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2">'
        .'<FatturaElettronicaHeader>'
        .'<CedentePrestatore>'.$party($cedentePiva, 'Cedente Srl').'</CedentePrestatore>'
        .'<CessionarioCommittente>'.$party($cessionarioPiva, 'Cessionario Srl').'</CessionarioCommittente>'
        .'</FatturaElettronicaHeader>'
        .'<FatturaElettronicaBody><DatiGenerali><DatiGeneraliDocumento>'
        .'<TipoDocumento>TD01</TipoDocumento><Divisa>EUR</Divisa><Data>'.$data.'</Data><Numero>'.$numero.'</Numero>'
        .'<ImportoTotaleDocumento>'.$total.'</ImportoTotaleDocumento>'
        .'</DatiGeneraliDocumento></DatiGenerali>'
        .'<DatiBeniServizi>'.$body.'</DatiBeniServizi></FatturaElettronicaBody>'
        .'</ns2:FatturaElettronica>';
}

it('imports an outbound XML as a Fattura, creates the customer and syncs the counter', function () {
    $xml = fatturaPaXml('12345678901', '04827193045', '2026/007', '2026-03-02', [
        ['Restyling', '1.00', '1000.00', '22.00'],
        ['Hosting', '2.00', '60.00', '22.00'],
    ], '1366.40');

    $result = app(FatturaPaImporter::class)->importOne('IT12345678901_00001.xml', $xml);

    expect($result['status'])->toBe('imported');
    expect($result['direction'])->toBe('outbound');

    $fattura = Fattura::query()->findOrFail($result['fattura_id']);
    expect([$fattura->year, $fattura->number])->toBe([2026, 7]);
    expect($fattura->total_cents)->toBe(136_640);
    expect($fattura->vat_cents)->toBe(24_640);

    $customer = Contact::query()->findOrFail($fattura->client_contact_id);
    expect($customer->vat_number)->toBe('04827193045');
    expect($customer->hasRole(ContactRole::Customer))->toBeTrue();

    // The next domestically issued fattura can't collide with number 7.
    expect(FatturaCounter::query()->find(2026)->last_number)->toBe(7);
});

it('skips an outbound XML already imported', function () {
    $xml = fatturaPaXml('12345678901', '04827193045', '7/2026', '2026-03-02', [['X', '1.00', '100.00', '22.00']], '122.00');
    $importer = app(FatturaPaImporter::class);

    expect($importer->importOne('a.xml', $xml)['status'])->toBe('imported');
    expect($importer->importOne('a.xml', $xml)['status'])->toBe('skipped');
    expect(Fattura::count())->toBe(1);
});

it('imports an inbound (purchase) XML as an idempotent LOSS entry against a vendor', function () {
    $xml = fatturaPaXml('01573850517', '12345678901', '42', '2026-04-01', [['Dominio', '1.00', '12.00', '22.00']], '14.64');
    $importer = app(FatturaPaImporter::class);

    $result = $importer->importOne('in.xml', $xml);

    expect($result['status'])->toBe('imported');
    expect($result['direction'])->toBe('inbound');
    $entry = FinancialEntry::query()->findOrFail($result['financial_entry_id']);
    expect($entry->type)->toBe(FinancialEntryType::Loss);
    expect($entry->amount_cents)->toBe(1_464);
    expect($entry->external_ref)->toBe('fpa:01573850517:2026:42');
    expect(Contact::query()->findOrFail($entry->contact_id)->hasRole(ContactRole::Vendor))->toBeTrue();

    expect($importer->importOne('in.xml', $xml)['status'])->toBe('skipped');
    expect(FinancialEntry::count())->toBe(1);
    expect(Fattura::count())->toBe(0);
});

it('reuses an existing contact matched by VAT number', function () {
    $existing = Contact::factory()->create(['vat_number' => '04827193045', 'roles' => [ContactRole::Collaborator->value]]);
    $xml = fatturaPaXml('12345678901', '04827193045', '2026/1', '2026-01-10', [['X', '1.00', '10.00', '22.00']], '12.20');

    $result = app(FatturaPaImporter::class)->importOne('x.xml', $xml);

    expect(Fattura::query()->find($result['fattura_id'])->client_contact_id)->toBe($existing->id);
    expect(Contact::count())->toBe(1);
});

it('fails, without writing anything, on XML that is not ours, unbalanced or malformed', function (string $xml, string $reason) {
    $result = app(FatturaPaImporter::class)->importOne('bad.xml', $xml);

    expect($result['status'])->toBe('failed');
    expect($result['reason'])->toContain($reason);
    expect(Fattura::count() + FinancialEntry::count() + Contact::count())->toBe(0);
})->with([
    'neither party is us' => [fatturaPaXml('11111111111', '22222222222', '1', '2026-01-01', [['X', '1.00', '10.00', '0.00']], '10.00'), 'Neither party'],
    'total mismatch' => [fatturaPaXml('12345678901', '04827193045', '1', '2026-01-01', [['X', '1.00', '10.00', '22.00']], '99.00'), 'Total mismatch'],
    'not XML' => ['this is not xml', 'XML parse error'],
]);

it('round-trips: an exported XML imports back to the same document', function () {
    $contact = Contact::factory()->create([
        'vat_number' => '04827193045',
        'address' => ['street' => 'Via Manzoni 12', 'city' => 'Milano', 'province' => 'MI', 'postal_code' => '20121', 'country' => 'IT'],
    ]);
    $original = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'issued_at' => '2026-06-15',
        'lines' => [
            ['description' => 'Sito vetrina', 'qty' => 1, 'unit_price_cents' => 180_000, 'vat_rate' => 22],
            ['description' => 'Ore extra', 'qty' => 3.5, 'unit_price_cents' => 3_750, 'vat_rate' => 22],
        ],
    ]);
    $xml = app(FatturaPaExporter::class)->export($original->id)['xml'];
    $snapshot = $original->only(['year', 'number', 'subtotal_cents', 'vat_cents', 'total_cents']);
    $original->delete();

    $result = app(FatturaPaImporter::class)->importOne('roundtrip.xml', $xml);

    expect($result['status'])->toBe('imported');
    $imported = Fattura::query()->findOrFail($result['fattura_id']);
    expect($imported->only(['year', 'number', 'subtotal_cents', 'vat_cents', 'total_cents']))->toBe($snapshot);
    expect($imported->client_contact_id)->toBe($contact->id);
});
