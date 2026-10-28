<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Services\Internal\FatturaPaExporter;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Settings\Services\Public\BusinessProfileService;

/**
 * FatturaPA (FPR12) export: the XML is a tax artefact the SdI validates
 * field by field, so these tests pin the structure and — above all —
 * the arithmetic consistency between lines, DatiRiepilogo and
 * ImportoTotaleDocumento.
 */
function configureCedente(array $overrides = []): void
{
    app(BusinessProfileService::class)->singleton()->update(array_merge([
        'denominazione' => 'Hive Web Studio di Mario Rossi',
        'codice_fiscale' => 'rssmra80a01h501u',
        'partita_iva' => '12345678901',
        'regime_fiscale' => 'RF19',
        'natura_default' => 'N2.2',
        'sede_indirizzo' => 'Via Roma',
        'sede_civico' => '1',
        'sede_cap' => '00100',
        'sede_comune' => 'Roma',
        'sede_provincia' => 'RM',
        'sede_nazione' => 'IT',
    ], $overrides));
}

function fiscalContact(array $overrides = []): Contact
{
    return Contact::factory()->create(array_merge([
        'name' => 'Pasticceria Bellavista',
        'ragione_sociale' => 'Pasticceria Bellavista S.r.l.',
        'vat_number' => '04827193045',
        'tax_code' => null,
        'sdi_code' => 'abc1234',
        'pec_email' => null,
        'address' => [
            'street' => 'Via Manzoni 12',
            'city' => 'Milano',
            'province' => 'MI',
            'postal_code' => '20121',
            'country' => 'IT',
        ],
    ], $overrides));
}

function xpath(string $xml): DOMXPath
{
    $dom = new DOMDocument;
    expect($dom->loadXML($xml))->toBeTrue();

    return new DOMXPath($dom);
}

it('exports cedente, cessionario, lines and a consistent VAT summary', function () {
    configureCedente(['regime_fiscale' => 'RF01']);
    $contact = fiscalContact();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'issued_at' => '2026-05-10',
        'year' => 2026,
        'lines' => [
            ['description' => 'Sviluppo sito', 'qty' => 1, 'unit_price_cents' => 250_000, 'vat_rate' => 22],
            ['description' => 'Hosting annuale', 'qty' => 2, 'unit_price_cents' => 6_000, 'vat_rate' => 22],
        ],
    ]);

    $out = app(FatturaPaExporter::class)->export($fattura->id);
    $xp = xpath($out['xml']);

    expect($out['filename'])->toMatch('/^IT12345678901_[A-Z0-9]{5}\.xml$/');
    expect($xp->evaluate('string(//CedentePrestatore//CodiceFiscale)'))->toBe('RSSMRA80A01H501U');
    expect($xp->evaluate('string(//CedentePrestatore//RegimeFiscale)'))->toBe('RF01');
    expect($xp->evaluate('string(//CodiceDestinatario)'))->toBe('ABC1234');
    expect($xp->evaluate('string(//CessionarioCommittente//Denominazione)'))->toBe('Pasticceria Bellavista S.r.l.');
    expect($xp->evaluate('string(//CessionarioCommittente//IdCodice)'))->toBe('04827193045');
    expect($xp->evaluate('string(//DatiGeneraliDocumento/Data)'))->toBe('2026-05-10');

    expect($xp->evaluate('count(//DettaglioLinee)'))->toBe(2.0);
    expect($xp->evaluate('string(//DettaglioLinee[2]/PrezzoTotale)'))->toBe('120.00');

    expect($xp->evaluate('count(//DatiRiepilogo)'))->toBe(1.0);
    expect($xp->evaluate('string(//DatiRiepilogo/ImponibileImporto)'))->toBe('2620.00');
    expect($xp->evaluate('string(//DatiRiepilogo/Imposta)'))->toBe('576.40');
    expect($xp->evaluate('string(//ImportoTotaleDocumento)'))->toBe('3196.40');
    expect($fattura->fresh()->total_cents)->toBe(319_640);
});

it('rounds Imposta on the per-rate taxable total so the SdI arithmetic check passes', function () {
    configureCedente();
    $contact = fiscalContact();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => array_fill(0, 10, ['description' => 'Micro', 'qty' => 1, 'unit_price_cents' => 10, 'vat_rate' => 22]),
    ]);

    $xp = xpath(app(FatturaPaExporter::class)->export($fattura->id)['xml']);

    // Imposta must equal ImponibileImporto × AliquotaIVA (1.00 × 22% = 0.22)
    expect($xp->evaluate('string(//DatiRiepilogo/ImponibileImporto)'))->toBe('1.00');
    expect($xp->evaluate('string(//DatiRiepilogo/Imposta)'))->toBe('0.22');
    expect($xp->evaluate('string(//ImportoTotaleDocumento)'))->toBe('1.22');
});

it('puts a Natura on every 0% line and summary, falling back to the profile default', function () {
    configureCedente(['natura_default' => 'N2.1']);
    $contact = fiscalContact();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [
            ['description' => 'Forfettario', 'qty' => 1, 'unit_price_cents' => 50_000, 'vat_rate' => 0],
        ],
    ]);

    $xp = xpath(app(FatturaPaExporter::class)->export($fattura->id)['xml']);

    expect($xp->evaluate('string(//DettaglioLinee/Natura)'))->toBe('N2.1');
    expect($xp->evaluate('string(//DatiRiepilogo/Natura)'))->toBe('N2.1');
    expect($xp->evaluate('string(//DatiRiepilogo/Imposta)'))->toBe('0.00');

    // A natura stored on the fattura wins over the profile default.
    $fattura->update(['natura' => 'N4']);
    $xp = xpath(app(FatturaPaExporter::class)->export($fattura->id)['xml']);
    expect($xp->evaluate('string(//DettaglioLinee/Natura)'))->toBe('N4');
});

it('falls back to the 0000000 code plus PEC when the customer has no SDI code', function () {
    configureCedente();
    $contact = fiscalContact(['sdi_code' => null, 'pec_email' => 'cliente@pec.it']);
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 1_000, 'vat_rate' => 22]],
    ]);

    $xp = xpath(app(FatturaPaExporter::class)->export($fattura->id)['xml']);

    expect($xp->evaluate('string(//CodiceDestinatario)'))->toBe('0000000');
    expect($xp->evaluate('string(//PECDestinatario)'))->toBe('cliente@pec.it');
});

it('refuses to export while the business profile is incomplete', function () {
    configureCedente(['partita_iva' => '', 'sede_cap' => '']);
    $contact = fiscalContact();
    $fattura = app(FatturaService::class)->create([
        'client_contact_id' => $contact->id,
        'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 1_000, 'vat_rate' => 22]],
    ]);

    expect(fn () => app(FatturaPaExporter::class)->export($fattura->id))
        ->toThrow(DomainException::class, 'partita_iva, sede.cap');
});

it('refuses to export for a customer without fiscal identifiers or address', function () {
    configureCedente();
    $noIds = fiscalContact(['vat_number' => null, 'tax_code' => null]);
    $noAddress = fiscalContact(['address' => ['street' => 'Via X', 'city' => 'Roma']]);

    foreach ([$noIds, $noAddress] as $contact) {
        $fattura = app(FatturaService::class)->create([
            'client_contact_id' => $contact->id,
            'lines' => [['description' => 'X', 'qty' => 1, 'unit_price_cents' => 1_000, 'vat_rate' => 22]],
        ]);

        expect(fn () => app(FatturaPaExporter::class)->export($fattura->id))->toThrow(DomainException::class);
    }
});
