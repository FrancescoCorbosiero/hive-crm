<?php

declare(strict_types=1);

namespace App\Domains\Websites\Listeners;

use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\DomainNames\Services\Public\DomainNamesService;
use App\Domains\Websites\Services\Public\WebsitesService;

/**
 * When a domain is registered and the operator opted into "create
 * website", spawn the sibling Website and back-link the domain to
 * it via the existing scalar FK (domain_names.website_id).
 *
 * Idempotent via the back-link: if the domain already has a
 * website_id (because autoLink found a matching host, or the
 * operator picked one manually, or this listener already ran), the
 * spawn is skipped.
 */
class CreateWebsiteFromDomainRegistration
{
    public function __construct(
        private readonly WebsitesService $websites,
        private readonly DomainNamesService $domains,
    ) {}

    public function handle(DomainRegistered $event): void
    {
        if ($event->websiteIntent === null) {
            return;
        }

        $domain = $this->domains->find($event->domainId);
        if (! $domain || $domain->websiteId !== null) {
            return;
        }

        $intent = $event->websiteIntent;
        $url = trim((string) ($intent['url'] ?? ''));
        if ($url === '') {
            return;
        }

        $existing = $this->websites->findByHost($url);
        if ($existing !== null) {
            $this->domains->linkWebsite($domain->id, $existing->id);

            return;
        }

        // Cost capture: if the operator entered setup cost on the
        // DomainName create form, the website is created with a
        // paymentIntent, so Finance's idempotent WebsiteCreated listener
        // books the LOSS entry (external_ref keyed on website id). No
        // domainIntent: that would loop back through
        // CreateDomainFromWebsite.
        $costCents = (int) ($intent['cost_amount_cents'] ?? 0);
        $paymentIntent = $costCents > 0 ? [
            'amount_cents' => $costCents,
            'currency' => (string) ($intent['cost_currency'] ?? config('app.currency', 'EUR')),
            'paid_at' => $intent['cost_paid_at'] ?? null,
            'method' => isset($intent['cost_method']) && $intent['cost_method'] !== ''
                ? (string) $intent['cost_method']
                : null,
        ] : null;

        $website = $this->websites->create([
            'name' => trim((string) ($intent['name'] ?? '')) ?: $domain->host,
            'url' => $url,
            'owner_contact_id' => $domain->ownerContactId,
            'owner_user_id' => $domain->ownerUserId,
        ], paymentIntent: $paymentIntent);

        $this->domains->linkWebsite($domain->id, $website->id);
    }
}
