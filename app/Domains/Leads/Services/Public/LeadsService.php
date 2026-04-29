<?php

declare(strict_types=1);

namespace App\Domains\Leads\Services\Public;

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Services\Public\ContactsService;
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
}
