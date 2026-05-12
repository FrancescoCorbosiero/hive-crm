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
use App\Domains\Websites\DTOs\WebsiteDTO;
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
     * optionally a linked Website. The Lead is archived (status=won,
     * converted_contact_id + converted_at populated).
     *
     * Idempotent: re-converting an already-converted lead throws so the
     * caller can decide what to do.
     *
     * @return array{contact: ContactDTO, website: ?WebsiteDTO}
     */
    public function convert(int $leadId, ?array $websiteAttributes = null): array
    {
        return DB::transaction(function () use ($leadId, $websiteAttributes) {
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
            }

            $lead->status = LeadStatus::Won;
            $lead->converted_contact_id = $contact->id;
            $lead->converted_at = now();
            $lead->save();

            LeadConverted::dispatch($lead->id, $contact->id, $website?->id);

            return ['contact' => $contact, 'website' => $website];
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
