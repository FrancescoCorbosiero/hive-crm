<?php

declare(strict_types=1);

/**
 * Fattura / FatturaPA configuration.
 *
 * The Italian electronic invoicing format (FatturaPA) carries the
 * Cedente Prestatore's fiscal identity inside each XML. To recognise
 * "this XML was issued by us" during import — and to populate the
 * Cedente block on export — these values must match the owner's
 * filed identity with the Agenzia delle Entrate.
 *
 * Test these values against an XML you actually submitted via SdI
 * before relying on imports.
 */
return [

    'cedente' => [
        'codice_fiscale' => env('OWNER_CODICE_FISCALE'),
        'partita_iva' => env('OWNER_PARTITA_IVA'),

        // Denominazione (or Nome/Cognome) on the FatturaPA Cedente block.
        // Forfettario users typically use the full business name here.
        'denominazione' => env('OWNER_DENOMINAZIONE'),

        // RF01 = ordinary regime, RF19 = forfettario. RF19 invoices
        // are VAT-exempt; the exporter writes Natura = N2.2 in
        // DatiRiepilogo by default for those.
        'regime_fiscale' => env('OWNER_REGIME_FISCALE', 'RF19'),

        // Default Natura for VAT-exempt invoices. Override on a
        // per-fattura basis via the natura column.
        'natura_default' => env('OWNER_NATURA_DEFAULT', 'N2.2'),

        // Italian fiscal address (Sede) required by the SdI XSD.
        'sede' => [
            'indirizzo' => env('OWNER_SEDE_INDIRIZZO'),
            'numero_civico' => env('OWNER_SEDE_CIVICO'),
            'cap' => env('OWNER_SEDE_CAP'),
            'comune' => env('OWNER_SEDE_COMUNE'),
            'provincia' => env('OWNER_SEDE_PROVINCIA'),
            'nazione' => env('OWNER_SEDE_NAZIONE', 'IT'),
        ],
    ],

];
