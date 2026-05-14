<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Internal;

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use DomainException;
use Illuminate\Support\Str;

/**
 * Render a Fattura into a FatturaPA (FPR12) XML string.
 *
 * The reverse of FatturaPaImporter: we take our internal row and
 * produce the canonical SdI artefact. Tax-relevant, so:
 *
 *   - Cedente identity is read from config (matches the env values
 *     used by the importer's direction guard — single source of truth).
 *   - Cessionario identity comes from the linked Contact. Address,
 *     P.IVA / CF, SDI code / PEC are all required by the XSD; if
 *     anything critical is missing the exporter throws so the user
 *     fixes the data BEFORE generating an XML they can't submit.
 *   - Natura on VAT=0 lines defaults to the fattura's `natura`
 *     (or the cedente's `natura_default`), satisfying the XSD rule
 *     that AliquotaIVA=0 must have a Natura.
 *   - ProgressivoInvio is a fresh 5-char alphanumeric per call.
 *     The XML filename matches the SdI convention: IT{piva}_{progressivo}.xml
 */
class FatturaPaExporter
{
    /**
     * @return array{filename: string, xml: string}
     */
    public function export(int $fatturaId): array
    {
        $fattura = Fattura::query()->findOrFail($fatturaId);

        $cedente = $this->cedenteConfig();
        $cessionario = $this->resolveCessionario((int) $fattura->client_contact_id);

        $progressivo = $this->progressivoInvio();
        $xml = $this->buildXml($fattura, $cedente, $cessionario, $progressivo);

        $filename = sprintf('IT%s_%s.xml', $cedente['partita_iva'], $progressivo);

        return ['filename' => $filename, 'xml' => $xml];
    }

    // ── Config / data resolution ───────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function cedenteConfig(): array
    {
        $cedente = app(\App\Domains\Settings\Services\Public\BusinessProfileService::class)->cedente();

        $cf = (string) ($cedente['codice_fiscale'] ?? '');
        $piva = (string) ($cedente['partita_iva'] ?? '');
        $deno = (string) ($cedente['denominazione'] ?? '');
        $sede = (array) ($cedente['sede'] ?? []);

        $missing = [];
        if ($cf === '') {
            $missing[] = 'codice_fiscale';
        }
        if ($piva === '') {
            $missing[] = 'partita_iva';
        }
        if ($deno === '') {
            $missing[] = 'denominazione';
        }
        foreach (['indirizzo', 'cap', 'comune', 'provincia'] as $k) {
            if (empty($sede[$k])) {
                $missing[] = 'sede.'.$k;
            }
        }

        if ($missing !== []) {
            throw new DomainException(
                'Missing business profile fields — fill them in Settings → Business profile: '
                .implode(', ', $missing),
            );
        }

        return [
            'codice_fiscale' => strtoupper($cf),
            'partita_iva' => $piva,
            'denominazione' => $deno,
            'regime_fiscale' => (string) ($cedente['regime_fiscale'] ?? 'RF19'),
            'sede' => $sede,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCessionario(int $contactId): array
    {
        $contact = Contact::query()->findOrFail($contactId);

        $piva = trim((string) $contact->vat_number);
        $cf = strtoupper(trim((string) $contact->tax_code));

        if ($piva === '' && $cf === '') {
            throw new DomainException(sprintf(
                "Contact #%d is missing both Partita IVA and Codice Fiscale; cannot generate a FatturaPA XML.",
                $contactId,
            ));
        }

        $address = (array) ($contact->address ?? []);
        foreach (['street', 'city', 'province', 'postal_code'] as $k) {
            if (empty($address[$k])) {
                throw new DomainException(sprintf(
                    "Contact #%d is missing address.%s — required by the FatturaPA XSD.",
                    $contactId,
                    $k,
                ));
            }
        }

        // SDI code (7 chars) → CodiceDestinatario. Otherwise the
        // SdI default '0000000' + PEC routing.
        $sdi = trim((string) $contact->sdi_code);
        $pec = trim((string) $contact->pec_email);
        $codiceDestinatario = ($sdi !== '' && strlen($sdi) === 7) ? strtoupper($sdi) : '0000000';

        return [
            'codice_fiscale' => $cf ?: null,
            'partita_iva' => $piva ?: null,
            'denominazione' => $contact->ragione_sociale ?: $contact->name,
            'sede' => $address,
            'codice_destinatario' => $codiceDestinatario,
            'pec_destinatario' => $pec ?: null,
        ];
    }

    private function progressivoInvio(): string
    {
        // 5 alphanumerics, no ambiguity-prone chars. The SdI accepts
        // 1..10 chars; 5 is conventional and matches the import side
        // expectation.
        return strtoupper(Str::random(5));
    }

    // ── XML rendering ──────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $cedente
     * @param  array<string, mixed>  $cessionario
     */
    private function buildXml(Fattura $fattura, array $cedente, array $cessionario, string $progressivo): string
    {
        $issued = $fattura->issued_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $numero = $fattura->year.'/'.str_pad((string) $fattura->number, 3, '0', STR_PAD_LEFT);
        $totalEuros = number_format($fattura->total_cents / 100, 2, '.', '');
        $currency = $fattura->currency ?: 'EUR';

        $documentNatura = $fattura->natura ?: (string) config('fattura.cedente.natura_default', 'N2.2');

        $lines = (array) $fattura->lines;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<p:FatturaElettronica versione="FPR12" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2">';

        // ── Header ──
        $xml .= '<FatturaElettronicaHeader>';

        $xml .= '<DatiTrasmissione>';
        $xml .= '<IdTrasmittente>';
        $xml .= '<IdPaese>IT</IdPaese>';
        $xml .= '<IdCodice>'.$this->esc($cedente['codice_fiscale']).'</IdCodice>';
        $xml .= '</IdTrasmittente>';
        $xml .= '<ProgressivoInvio>'.$this->esc($progressivo).'</ProgressivoInvio>';
        $xml .= '<FormatoTrasmissione>FPR12</FormatoTrasmissione>';
        $xml .= '<CodiceDestinatario>'.$this->esc($cessionario['codice_destinatario']).'</CodiceDestinatario>';
        if (! empty($cessionario['pec_destinatario'])) {
            $xml .= '<PECDestinatario>'.$this->esc($cessionario['pec_destinatario']).'</PECDestinatario>';
        }
        $xml .= '</DatiTrasmissione>';

        $xml .= '<CedentePrestatore>';
        $xml .= '<DatiAnagrafici>';
        $xml .= '<IdFiscaleIVA><IdPaese>IT</IdPaese><IdCodice>'.$this->esc($cedente['partita_iva']).'</IdCodice></IdFiscaleIVA>';
        $xml .= '<CodiceFiscale>'.$this->esc($cedente['codice_fiscale']).'</CodiceFiscale>';
        $xml .= '<Anagrafica><Denominazione>'.$this->esc($cedente['denominazione']).'</Denominazione></Anagrafica>';
        $xml .= '<RegimeFiscale>'.$this->esc($cedente['regime_fiscale']).'</RegimeFiscale>';
        $xml .= '</DatiAnagrafici>';
        $xml .= $this->sedeBlock(
            indirizzo: (string) ($cedente['sede']['indirizzo'] ?? ''),
            civico: (string) ($cedente['sede']['numero_civico'] ?? ''),
            cap: (string) ($cedente['sede']['cap'] ?? ''),
            comune: (string) ($cedente['sede']['comune'] ?? ''),
            provincia: (string) ($cedente['sede']['provincia'] ?? ''),
            nazione: (string) ($cedente['sede']['nazione'] ?? 'IT'),
        );
        $xml .= '</CedentePrestatore>';

        $xml .= '<CessionarioCommittente>';
        $xml .= '<DatiAnagrafici>';
        if (! empty($cessionario['partita_iva'])) {
            $xml .= '<IdFiscaleIVA><IdPaese>IT</IdPaese><IdCodice>'.$this->esc($cessionario['partita_iva']).'</IdCodice></IdFiscaleIVA>';
        }
        if (! empty($cessionario['codice_fiscale'])) {
            $xml .= '<CodiceFiscale>'.$this->esc($cessionario['codice_fiscale']).'</CodiceFiscale>';
        }
        $xml .= '<Anagrafica><Denominazione>'.$this->esc($cessionario['denominazione']).'</Denominazione></Anagrafica>';
        $xml .= '</DatiAnagrafici>';
        $xml .= $this->sedeBlock(
            indirizzo: (string) ($cessionario['sede']['street'] ?? ''),
            civico: '',
            cap: (string) ($cessionario['sede']['postal_code'] ?? ''),
            comune: (string) ($cessionario['sede']['city'] ?? ''),
            provincia: (string) ($cessionario['sede']['province'] ?? ''),
            nazione: (string) ($cessionario['sede']['country'] ?? 'IT'),
        );
        $xml .= '</CessionarioCommittente>';

        $xml .= '</FatturaElettronicaHeader>';

        // ── Body ──
        $xml .= '<FatturaElettronicaBody>';
        $xml .= '<DatiGenerali><DatiGeneraliDocumento>';
        $xml .= '<TipoDocumento>TD01</TipoDocumento>';
        $xml .= '<Divisa>'.$this->esc($currency).'</Divisa>';
        $xml .= '<Data>'.$this->esc($issued).'</Data>';
        $xml .= '<Numero>'.$this->esc($numero).'</Numero>';
        $xml .= '<ImportoTotaleDocumento>'.$this->esc($totalEuros).'</ImportoTotaleDocumento>';
        $xml .= '</DatiGeneraliDocumento></DatiGenerali>';

        $xml .= '<DatiBeniServizi>';

        // Lines.
        $riepilogo = [];  // [rateKey => [imponibileCents, imposta, natura]]
        foreach ($lines as $i => $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unit = (int) ($line['unit_price_cents'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $lineSubtotal = (int) round($qty * $unit);
            $lineVat = (int) round($lineSubtotal * $rate / 100);
            $natura = $rate == 0.0 ? ($line['natura'] ?? $documentNatura) : null;

            $xml .= '<DettaglioLinee>';
            $xml .= '<NumeroLinea>'.($i + 1).'</NumeroLinea>';
            $xml .= '<Descrizione>'.$this->esc((string) ($line['description'] ?? 'Riga')).'</Descrizione>';
            $xml .= '<Quantita>'.number_format($qty, 2, '.', '').'</Quantita>';
            $xml .= '<PrezzoUnitario>'.number_format($unit / 100, 2, '.', '').'</PrezzoUnitario>';
            $xml .= '<PrezzoTotale>'.number_format($lineSubtotal / 100, 2, '.', '').'</PrezzoTotale>';
            $xml .= '<AliquotaIVA>'.number_format($rate, 2, '.', '').'</AliquotaIVA>';
            if ($natura !== null) {
                $xml .= '<Natura>'.$this->esc((string) $natura).'</Natura>';
            }
            $xml .= '</DettaglioLinee>';

            $key = number_format($rate, 2, '.', '').'|'.($natura ?? '');
            if (! isset($riepilogo[$key])) {
                $riepilogo[$key] = ['imponibile' => 0, 'imposta' => 0, 'rate' => $rate, 'natura' => $natura];
            }
            $riepilogo[$key]['imponibile'] += $lineSubtotal;
            $riepilogo[$key]['imposta'] += $lineVat;
        }

        foreach ($riepilogo as $r) {
            $xml .= '<DatiRiepilogo>';
            $xml .= '<AliquotaIVA>'.number_format($r['rate'], 2, '.', '').'</AliquotaIVA>';
            if ($r['natura'] !== null) {
                $xml .= '<Natura>'.$this->esc((string) $r['natura']).'</Natura>';
            }
            $xml .= '<ImponibileImporto>'.number_format($r['imponibile'] / 100, 2, '.', '').'</ImponibileImporto>';
            $xml .= '<Imposta>'.number_format($r['imposta'] / 100, 2, '.', '').'</Imposta>';
            $xml .= '</DatiRiepilogo>';
        }

        $xml .= '</DatiBeniServizi>';
        $xml .= '</FatturaElettronicaBody>';

        $xml .= '</p:FatturaElettronica>';

        return $xml;
    }

    private function sedeBlock(
        string $indirizzo,
        string $civico,
        string $cap,
        string $comune,
        string $provincia,
        string $nazione,
    ): string {
        $xml = '<Sede>';
        $xml .= '<Indirizzo>'.$this->esc($indirizzo).'</Indirizzo>';
        if ($civico !== '') {
            $xml .= '<NumeroCivico>'.$this->esc($civico).'</NumeroCivico>';
        }
        $xml .= '<CAP>'.$this->esc($cap).'</CAP>';
        $xml .= '<Comune>'.$this->esc($comune).'</Comune>';
        if ($provincia !== '') {
            $xml .= '<Provincia>'.$this->esc(strtoupper(substr($provincia, 0, 2))).'</Provincia>';
        }
        $xml .= '<Nazione>'.$this->esc(strtoupper(substr($nazione ?: 'IT', 0, 2))).'</Nazione>';
        $xml .= '</Sede>';

        return $xml;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
