<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Internal;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

/**
 * Parse a FatturaPA (FPR12 / FPA12) XML and persist the corresponding
 * Fattura row(s). High-stakes — tax-relevant data — so every step is
 * defensive:
 *
 *   - Idempotent by (year, number): re-importing the same XML produces
 *     a `skipped` entry, not a duplicate or an exception.
 *   - Direction check: rejects XMLs where we aren't the Cedente
 *     (purchase invoices need a separate flow).
 *   - Math check: subtotal + VAT recomputed from lines must match
 *     `ImportoTotaleDocumento` within €0.01.
 *   - Counter sync: after each successful import, the per-year
 *     FatturaCounter is bumped so future domestically-issued fatture
 *     can't collide with imported numbers.
 *
 * Returns a flat result array per file. Callers (UI / CLI) format the
 * summary in whichever style they need.
 */
class FatturaPaImporter
{
    /**
     * Result shape per XML processed:
     *   ['status' => 'imported'|'skipped'|'failed',
     *    'filename' => string,
     *    'fattura_id' => ?int,
     *    'year' => ?int,
     *    'number' => ?int,
     *    'reason' => ?string]
     *
     * @param  array<int, array{filename: string, contents: string}>  $files
     * @return array<int, array<string, mixed>>
     */
    public function importMany(array $files, ?int $ownerUserId = null): array
    {
        $results = [];
        foreach ($files as $file) {
            $results[] = $this->importOne($file['filename'], $file['contents'], $ownerUserId);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function importOne(string $filename, string $xml, ?int $ownerUserId = null): array
    {
        try {
            // libxml is noisy by default — capture errors locally.
            $previousUse = libxml_use_internal_errors(true);
            libxml_clear_errors();

            // FatturaPA wraps everything under a versioned namespace
            // prefix (e.g. <ns2:FatturaElettronica>). SimpleXML's
            // tree traversal silently skips namespaced children unless
            // we register the namespace. Easier: strip the prefix from
            // every opening/closing tag, then walk the tree without
            // namespace awareness.
            $normalized = preg_replace_callback(
                '#(</?)[a-z][\w-]*:#i',
                static fn (array $m): string => $m[1],
                $xml,
            );

            $doc = simplexml_load_string($normalized);
            if ($doc === false) {
                $messages = collect(libxml_get_errors())->pluck('message')->implode('; ');
                throw new DomainException('XML parse error: '.trim($messages ?: 'invalid XML'));
            }

            $parsed = $this->parse($doc);
            $this->guardTotals($parsed);

            $direction = $this->detectDirection($parsed);

            return match ($direction) {
                'outbound' => $this->persistOutbound($filename, $parsed, $ownerUserId),
                'inbound' => $this->persistInbound($filename, $parsed, $ownerUserId),
            };
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'filename' => $filename,
                'fattura_id' => null,
                'year' => null,
                'number' => null,
                'reason' => $e->getMessage(),
            ];
        } finally {
            if (isset($previousUse)) {
                libxml_use_internal_errors($previousUse);
            }
        }
    }

    // ── Parsing ────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function parse(SimpleXMLElement $doc): array
    {
        $header = $doc->FatturaElettronicaHeader;
        $body = $doc->FatturaElettronicaBody;

        if (! $header || ! $body) {
            throw new DomainException('Missing FatturaElettronicaHeader or Body.');
        }

        $cedente = $this->parseAnagrafica($header->CedentePrestatore);
        $cessionario = $this->parseAnagrafica($header->CessionarioCommittente);

        $general = $body->DatiGenerali->DatiGeneraliDocumento;
        if (! $general) {
            throw new DomainException('Missing DatiGeneraliDocumento.');
        }

        $numero = trim((string) $general->Numero);
        $data = trim((string) $general->Data);
        $issuedAt = $this->parseDate($data);
        [$year, $number] = $this->parseNumero($numero, $issuedAt);

        $currency = trim((string) $general->Divisa) ?: 'EUR';
        $totalEuros = $this->parseMoney((string) $general->ImportoTotaleDocumento);

        $lines = $this->parseLines($body->DatiBeniServizi->DettaglioLinee);
        [$subtotalCents, $vatCents, $totalCentsFromLines] = $this->computeTotals($lines);

        return [
            'cedente' => $cedente,
            'cessionario' => $cessionario,
            'year' => $year,
            'number' => $number,
            'issued_at' => $issuedAt,
            'currency' => $currency,
            'lines' => $lines,
            'subtotal_cents' => $subtotalCents,
            'vat_cents' => $vatCents,
            'total_cents' => $totalCentsFromLines,
            'declared_total_cents' => $totalEuros,
        ];
    }

    /**
     * @return array{
     *     denominazione: ?string,
     *     ragione_sociale: ?string,
     *     codice_fiscale: ?string,
     *     partita_iva: ?string,
     *     pec_email: ?string,
     *     sdi_code: ?string,
     *     address: ?array<string,string>
     * }
     */
    private function parseAnagrafica(?SimpleXMLElement $node): array
    {
        if ($node === null) {
            return [
                'denominazione' => null, 'ragione_sociale' => null,
                'codice_fiscale' => null, 'partita_iva' => null,
                'pec_email' => null, 'sdi_code' => null, 'address' => null,
            ];
        }

        $dati = $node->DatiAnagrafici ?? null;
        $sede = $node->Sede ?? null;
        $anag = $dati?->Anagrafica ?? null;

        $denominazione = $anag?->Denominazione ? (string) $anag->Denominazione : null;
        $nome = $anag?->Nome ? (string) $anag->Nome : null;
        $cognome = $anag?->Cognome ? (string) $anag->Cognome : null;
        $fullName = $denominazione ?? trim(($nome ?? '').' '.($cognome ?? '')) ?: null;

        return [
            'denominazione' => $fullName,
            'ragione_sociale' => $denominazione,
            'codice_fiscale' => $dati?->CodiceFiscale ? strtoupper((string) $dati->CodiceFiscale) : null,
            'partita_iva' => $dati?->IdFiscaleIVA?->IdCodice ? (string) $dati->IdFiscaleIVA->IdCodice : null,
            'pec_email' => null, // PEC lives on DatiTrasmissione, populated separately when needed
            'sdi_code' => null,
            'address' => $sede ? [
                'street' => trim((string) ($sede->Indirizzo ?? '').' '.(string) ($sede->NumeroCivico ?? '')) ?: null,
                'city' => $sede->Comune ? (string) $sede->Comune : null,
                'province' => $sede->Provincia ? (string) $sede->Provincia : null,
                'postal_code' => $sede->CAP ? (string) $sede->CAP : null,
                'country' => $sede->Nazione ? (string) $sede->Nazione : null,
            ] : null,
        ];
    }

    /**
     * Parse Numero into [year, number]. Accepts "2026/004", "004/2026",
     * "2026-004", "004", "4". Defaults the year to the document's
     * issued_at year when not present in the numero string.
     *
     * @return array{0: int, 1: int}
     */
    private function parseNumero(string $numero, Carbon $issuedAt): array
    {
        $numero = trim($numero);
        if ($numero === '') {
            throw new DomainException('Numero is empty.');
        }

        // "2026/004" or "2026-004"
        if (preg_match('#^(\d{4})[/-](\d+)$#', $numero, $m)) {
            return [(int) $m[1], (int) ltrim($m[2], '0')];
        }
        // "004/2026" or "004-2026"
        if (preg_match('#^(\d+)[/-](\d{4})$#', $numero, $m)) {
            return [(int) $m[2], (int) ltrim($m[1], '0')];
        }
        // Plain digits → use issued_at's year
        if (preg_match('#^\d+$#', $numero)) {
            $n = (int) ltrim($numero, '0');
            if ($n <= 0) {
                throw new DomainException("Numero '{$numero}' is not positive.");
            }

            return [$issuedAt->year, $n];
        }

        throw new DomainException("Unsupported Numero format: '{$numero}'.");
    }

    private function parseDate(string $data): Carbon
    {
        try {
            return Carbon::parse($data);
        } catch (\Throwable) {
            throw new DomainException("Invalid Data '{$data}'.");
        }
    }

    /**
     * Convert a money string like "150.00" or "1.234,56" to integer cents.
     */
    private function parseMoney(string $raw): int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0;
        }

        // Italian "1.234,56" → "1234.56"; English "1,234.56" → "1234.56"
        if (preg_match('/,\d{1,2}$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        return (int) round(((float) $raw) * 100);
    }

    /**
     * @return array<int, array{description: string, qty: float, unit_price_cents: int, vat_rate: float}>
     */
    private function parseLines(?SimpleXMLElement $linee): array
    {
        if ($linee === null) {
            return [];
        }

        $out = [];
        foreach ($linee as $line) {
            $out[] = [
                'description' => trim((string) $line->Descrizione) ?: 'Riga',
                'qty' => (float) ((string) ($line->Quantita ?? '1') ?: '1'),
                'unit_price_cents' => $this->parseMoney((string) ($line->PrezzoUnitario ?? '0')),
                'vat_rate' => (float) ((string) ($line->AliquotaIVA ?? '0') ?: '0'),
            ];
        }

        if ($out === []) {
            throw new DomainException('No DettaglioLinee found.');
        }

        return $out;
    }

    /**
     * @param  array<int, array<string,mixed>>  $lines
     * @return array{0:int,1:int,2:int}
     */
    private function computeTotals(array $lines): array
    {
        $subtotal = 0;
        $vat = 0;
        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unit = (int) ($line['unit_price_cents'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $lineSubtotal = (int) round($qty * $unit);
            $subtotal += $lineSubtotal;
            $vat += (int) round($lineSubtotal * $rate / 100);
        }

        return [$subtotal, $vat, $subtotal + $vat];
    }

    // ── Guards ─────────────────────────────────────────────────────────

    /**
     * Detect whether this XML is outbound (we are the Cedente — sale,
     * → Fattura row) or inbound (we are the Cessionario — purchase,
     * → FinancialEntry(loss) row). Either side matches by Codice
     * Fiscale or Partita IVA — whichever identifier the XML carries.
     *
     * Throws if neither side matches us, or if both do (impossible
     * data, refuse to guess). Refuses to run when owner config is
     * blank: silently misclassifying direction would corrupt the
     * ledger.
     *
     * @return 'outbound'|'inbound'
     */
    private function detectDirection(array $parsed): string
    {
        $cedente = app(\App\Domains\Settings\Services\Public\BusinessProfileService::class)->cedente();
        $ownerCf = strtoupper((string) ($cedente['codice_fiscale'] ?? ''));
        $ownerPiva = (string) ($cedente['partita_iva'] ?? '');

        if ($ownerCf === '' && $ownerPiva === '') {
            throw new DomainException('Owner identity is not configured. Fill Codice Fiscale / Partita IVA in Settings → Business profile before importing.');
        }

        $matches = function (array $party) use ($ownerCf, $ownerPiva): bool {
            $cf = strtoupper((string) ($party['codice_fiscale'] ?? ''));
            $piva = (string) ($party['partita_iva'] ?? '');
            $cfMatches = $ownerCf !== '' && $cf !== '' && $ownerCf === $cf;
            $pivaMatches = $ownerPiva !== '' && $piva !== '' && $ownerPiva === $piva;

            return $cfMatches || $pivaMatches;
        };

        $weAreCedente = $matches($parsed['cedente']);
        $weAreCessionario = $matches($parsed['cessionario']);

        if ($weAreCedente && $weAreCessionario) {
            throw new DomainException('XML lists the owner as both Cedente and Cessionario — refusing to guess direction.');
        }

        if ($weAreCedente) {
            return 'outbound';
        }

        if ($weAreCessionario) {
            return 'inbound';
        }

        throw new DomainException(sprintf(
            "Neither party in the XML matches the configured owner (%s / %s). Cedente: %s / %s. Cessionario: %s / %s.",
            $ownerCf ?: '—', $ownerPiva ?: '—',
            $parsed['cedente']['codice_fiscale'] ?? '—', $parsed['cedente']['partita_iva'] ?? '—',
            $parsed['cessionario']['codice_fiscale'] ?? '—', $parsed['cessionario']['partita_iva'] ?? '—',
        ));
    }

    /**
     * Outbound (sale): we are the Cedente. Materialize a Fattura row,
     * bump the per-year counter, resolve / create the Cessionario as
     * a customer Contact.
     *
     * @return array<string, mixed>
     */
    private function persistOutbound(string $filename, array $parsed, ?int $ownerUserId): array
    {
        return DB::transaction(function () use ($filename, $parsed, $ownerUserId) {
            $existing = Fattura::query()
                ->where('year', $parsed['year'])
                ->where('number', $parsed['number'])
                ->first();

            if ($existing) {
                return [
                    'status' => 'skipped',
                    'direction' => 'outbound',
                    'filename' => $filename,
                    'fattura_id' => $existing->id,
                    'year' => $parsed['year'],
                    'number' => $parsed['number'],
                    'reason' => 'already_exists',
                ];
            }

            $contactId = $this->resolveOrCreateContact($parsed['cessionario'], ContactRole::Customer);

            $fattura = Fattura::query()->create([
                'year' => $parsed['year'],
                'number' => $parsed['number'],
                'client_contact_id' => $contactId,
                'issued_at' => $parsed['issued_at'],
                'lines' => $parsed['lines'],
                'subtotal_cents' => $parsed['subtotal_cents'],
                'vat_cents' => $parsed['vat_cents'],
                'total_cents' => $parsed['total_cents'],
                'currency' => $parsed['currency'],
                'payment_status' => PaymentStatus::Unpaid->value,
                'owner_user_id' => $ownerUserId,
            ]);

            $this->syncCounter($parsed['year'], $parsed['number']);

            return [
                'status' => 'imported',
                'direction' => 'outbound',
                'filename' => $filename,
                'fattura_id' => $fattura->id,
                'year' => $parsed['year'],
                'number' => $parsed['number'],
                'reason' => null,
            ];
        });
    }

    /**
     * Inbound (purchase): we are the Cessionario. Materialize a
     * FinancialEntry(loss) tagged with a stable external_ref so
     * re-importing is idempotent. The Cedente resolves to a vendor
     * Contact; we do NOT touch the Fattura counter — purchase
     * invoices are someone else's numbering space.
     *
     * @return array<string, mixed>
     */
    private function persistInbound(string $filename, array $parsed, ?int $ownerUserId): array
    {
        return DB::transaction(function () use ($filename, $parsed, $ownerUserId) {
            $cedentePiva = (string) ($parsed['cedente']['partita_iva']
                ?? $parsed['cedente']['codice_fiscale']
                ?? 'unknown');

            $externalRef = sprintf(
                'fpa:%s:%d:%d',
                $cedentePiva,
                $parsed['year'],
                $parsed['number'],
            );

            $existing = FinancialEntry::query()
                ->where('external_ref', $externalRef)
                ->first();

            if ($existing) {
                return [
                    'status' => 'skipped',
                    'direction' => 'inbound',
                    'filename' => $filename,
                    'financial_entry_id' => $existing->id,
                    'external_ref' => $externalRef,
                    'reason' => 'already_exists',
                ];
            }

            $vendorId = $this->resolveOrCreateContact($parsed['cedente'], ContactRole::Vendor);
            $vendorName = $parsed['cedente']['denominazione']
                ?? $parsed['cedente']['ragione_sociale']
                ?? 'Fornitore';

            $entry = FinancialEntry::query()->create([
                'type' => FinancialEntryType::Loss->value,
                'amount_cents' => $parsed['total_cents'],
                'currency' => $parsed['currency'],
                'occurred_at' => $parsed['issued_at'],
                'description' => sprintf(
                    'Fattura %d/%03d da %s',
                    $parsed['year'],
                    $parsed['number'],
                    $vendorName,
                ),
                'category' => null,
                'contact_id' => $vendorId,
                'external_ref' => $externalRef,
                'owner_user_id' => $ownerUserId,
            ]);

            return [
                'status' => 'imported',
                'direction' => 'inbound',
                'filename' => $filename,
                'financial_entry_id' => $entry->id,
                'external_ref' => $externalRef,
                'reason' => null,
            ];
        });
    }

    private function guardTotals(array $parsed): void
    {
        $diff = abs($parsed['total_cents'] - $parsed['declared_total_cents']);
        if ($diff > 1) {
            throw new DomainException(sprintf(
                'Total mismatch: lines sum to %.2f €, ImportoTotaleDocumento declares %.2f €.',
                $parsed['total_cents'] / 100,
                $parsed['declared_total_cents'] / 100,
            ));
        }
    }

    // ── Persistence helpers ────────────────────────────────────────────

    /**
     * Match contact by Partita IVA, then Codice Fiscale; create if
     * neither hits. The role tag (Customer for outbound counterparties,
     * Vendor for inbound) is set on create only — an existing contact
     * keeps whichever roles it already had.
     */
    private function resolveOrCreateContact(array $party, ContactRole $defaultRole): int
    {
        $piva = $party['partita_iva'];
        $cf = $party['codice_fiscale'];

        if ($piva) {
            $existing = Contact::query()->where('vat_number', $piva)->first();
            if ($existing) {
                return $existing->id;
            }
        }
        if ($cf) {
            $existing = Contact::query()->where('tax_code', $cf)->first();
            if ($existing) {
                return $existing->id;
            }
        }

        $name = $party['denominazione'] ?: ($party['ragione_sociale'] ?: 'Sconosciuto');

        $contact = Contact::query()->create([
            'name' => $name,
            'ragione_sociale' => $party['ragione_sociale'],
            'vat_number' => $piva,
            'tax_code' => $cf,
            'address' => $party['address'],
            'roles' => [$defaultRole->value],
            'do_not_email' => false,
        ]);

        return $contact->id;
    }

    /**
     * Bump the per-year FatturaCounter to max(current, importedNumber)
     * so future domestically-issued fatture can't reuse this number.
     */
    private function syncCounter(int $year, int $importedNumber): void
    {
        $counter = FatturaCounter::query()
            ->lockForUpdate()
            ->find($year);

        if (! $counter) {
            FatturaCounter::query()->create([
                'year' => $year,
                'last_number' => $importedNumber,
            ]);

            return;
        }

        if ($counter->last_number < $importedNumber) {
            $counter->last_number = $importedNumber;
            $counter->save();
        }
    }
}
