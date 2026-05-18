<?php

declare(strict_types=1);

namespace App\Domains\Leads\Services\Public;

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Leads\DTOs\LeadDTO;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Events\LeadConverted;
use App\Domains\Leads\Models\Lead;
use App\Domains\Quotations\DTOs\QuotationDTO;
use App\Domains\Quotations\Services\Public\QuotationsService;
use App\Domains\Websites\DTOs\WebsiteDTO;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Services\Public\WebsitesService;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Public surface of the Leads domain.
 *
 * The convert() use case is the cross-domain conductor: it creates the
 * downstream Contact (and optionally Website) through the *other* domains'
 * public services — never by importing their models directly. This keeps
 * the architectural boundary intact even though the workflow spans
 * three domains.
 */
class LeadsService
{
    public function __construct(
        private readonly ContactsService $contacts,
        private readonly WebsitesService $websites,
        private readonly FatturaService $fatture,
        private readonly QuotationsService $quotations,
    ) {}

    public function find(int $id): ?LeadDTO
    {
        $lead = Lead::query()->find($id);

        return $lead ? LeadDTO::fromModel($lead) : null;
    }

    /**
     * @return Collection<string, int> status value => open lead count
     */
    public function pipelineCounts(): Collection
    {
        $rows = Lead::query()
            ->open()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(LeadStatus::pipeline())
            ->mapWithKeys(fn (LeadStatus $s) => [$s->value => (int) ($rows[$s->value] ?? 0)]);
    }

    /**
     * Pipeline value summed per stage. Mixed-currency leads are bucketed by
     * currency and the dominant currency wins; the rest are converted to
     * 0 cents (we don't pretend to do FX). For a single-currency workspace
     * this is the right thing.
     *
     * @return Collection<string, array{count:int, cents:int, currency:string}>
     */
    public function pipelineValueByStage(): Collection
    {
        $rows = Lead::query()
            ->open()
            ->selectRaw('status, estimated_value_currency AS currency, COUNT(*) AS total, COALESCE(SUM(estimated_value_cents), 0) AS cents')
            ->groupBy('status', 'estimated_value_currency')
            ->get();

        $default = (string) config('app.currency', 'EUR');

        return collect(LeadStatus::pipeline())
            ->mapWithKeys(function (LeadStatus $stage) use ($rows, $default) {
                $stageRows = $rows->where('status', $stage->value);

                if ($stageRows->isEmpty()) {
                    return [$stage->value => ['count' => 0, 'cents' => 0, 'currency' => $default]];
                }

                $dominant = $stageRows->sortByDesc('cents')->first();

                return [$stage->value => [
                    'count' => (int) $stageRows->sum('total'),
                    'cents' => (int) ($dominant->cents ?? 0),
                    'currency' => (string) ($dominant->currency ?? $default),
                ]];
            });
    }

    /**
     * Convert a Lead into a Contact (with the customer role) and
     * optionally a linked Website and / or a draft Quotation. The Lead
     * is archived (status=won, converted_contact_id + converted_at
     * populated).
     *
     * The quotation, when requested, is created in Draft status with a
     * single placeholder line auto-derived from the lead (description =
     * lead name, qty 1, unit price = lead.estimated_value or 0, VAT 22%).
     * The operator edits it from the QuotationResource afterwards.
     *
     * Idempotent: re-converting an already-converted lead throws so the
     * caller can decide what to do. Everything happens in one
     * transaction — Contact, Website and Quotation either all materialise
     * or none do.
     *
     * @param  array<string,mixed>|null  $websiteAttributes
     *                                                       Pass to create a Website with these attributes (owner_contact_id
     *                                                       and owner_user_id are filled in). Null = no Website.
     * @param  array<string,mixed>|null  $quotationAttributes
     *                                                         Pass an empty array `[]` to create a Quotation with defaults, or
     *                                                         override individual keys (name, lines, currency, ...). Null = no
     *                                                         Quotation.
     * @param  array{amount_cents: int, currency?: string, paid_at?: \DateTimeInterface|string|null, method?: ?string}|null  $websitePaymentIntent
     *                                                                                                                                              When the website is created AND the operator captured its setup
     *                                                                                                                                              cost, the service dispatches WebsiteCreated with this payload so
     *                                                                                                                                              Finance materialises a LOSS entry (idempotent via external_ref).
     * @return array{contact: ContactDTO, website: ?WebsiteDTO, quotation: ?QuotationDTO}
     */
    public function convert(
        int $leadId,
        ?array $websiteAttributes = null,
        ?array $quotationAttributes = null,
        ?array $websitePaymentIntent = null,
    ): array {
        return DB::transaction(function () use ($leadId, $websiteAttributes, $quotationAttributes, $websitePaymentIntent) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($leadId);

            if ($lead->isConverted()) {
                throw new DomainException("Lead {$leadId} is already converted.");
            }

            $contact = $this->contacts->create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'roles' => [ContactRole::Customer->value],
                'owner_user_id' => $lead->owner_user_id,
            ]);

            $website = null;
            if ($websiteAttributes !== null) {
                $website = $this->websites->create(array_merge([
                    'owner_contact_id' => $contact->id,
                    'owner_user_id' => $lead->owner_user_id,
                ], $websiteAttributes));

                // Cost capture: if the operator entered website setup cost
                // on the convert modal, fan out a paymentIntent-only
                // WebsiteCreated so Finance's existing idempotent listener
                // creates the LOSS entry (external_ref = "website_setup:{id}").
                // domainIntent is null — we don't auto-register a domain
                // from a lead conversion.
                if ($websitePaymentIntent !== null
                    && (int) ($websitePaymentIntent['amount_cents'] ?? 0) > 0) {
                    WebsiteCreated::dispatch($website->id, $websitePaymentIntent, null);
                }
            }

            $quotation = null;
            if ($quotationAttributes !== null) {
                $quotationModel = $this->quotations->create(array_merge([
                    'name' => $lead->name,
                    'client_contact_id' => $contact->id,
                    'lead_id' => $lead->id,
                    'lines' => [[
                        'description' => $lead->name,
                        'qty' => 1,
                        'unit_price_cents' => (int) ($lead->estimated_value_cents ?? 0),
                        'vat_rate' => 22,
                    ]],
                    'currency' => $lead->estimated_value_currency ?: (string) config('app.currency', 'EUR'),
                    'owner_user_id' => $lead->owner_user_id,
                ], $quotationAttributes));
                $quotation = QuotationDTO::fromModel($quotationModel);
            }

            $lead->status = LeadStatus::Won;
            $lead->converted_contact_id = $contact->id;
            $lead->converted_at = now();
            $lead->save();

            LeadConverted::dispatch($lead->id, $contact->id, $website?->id, $quotation?->id);

            return ['contact' => $contact, 'website' => $website, 'quotation' => $quotation];
        });
    }

    /**
     * Spawn a draft Fattura against the lead's already-converted Contact.
     * One line, qty 1, unit price = lead.estimated_value (or 0 when the
     * lead carries no value — the operator fills it in on the form).
     * VAT rate defaults to 22 (Italian standard); description is the
     * lead's name as a placeholder.
     *
     * @return int The new Fattura id, for caller redirection.
     */
    public function issueInvoice(int $leadId, int $vatRate = 22): int
    {
        return DB::transaction(function () use ($leadId, $vatRate): int {
            $lead = Lead::query()->lockForUpdate()->findOrFail($leadId);

            if (! $lead->isConverted()) {
                throw new DomainException("Lead {$leadId} has no converted contact yet.");
            }

            $fattura = $this->fatture->create([
                'client_contact_id' => $lead->converted_contact_id,
                'lines' => [[
                    'description' => $lead->name,
                    'qty' => 1,
                    'unit_price_cents' => (int) ($lead->estimated_value_cents ?? 0),
                    'vat_rate' => $vatRate,
                ]],
                'currency' => $lead->estimated_value_currency ?: (string) config('app.currency', 'EUR'),
                'owner_user_id' => $lead->owner_user_id,
            ]);

            return $fattura->id;
        });
    }
}
