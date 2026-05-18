<?php

declare(strict_types=1);

namespace App\Domains\Websites\Listeners;

use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\DomainNames\Models\DomainName;
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
    public function __construct(private readonly WebsitesService $websites) {}

    public function handle(DomainRegistered $event): void
    {
        if ($event->websiteIntent === null) {
            return;
        }

        $domain = DomainName::query()->find($event->domainId);
        if (! $domain) {
            return;
        }

        if ($domain->website_id !== null) {
            return;
        }

        $intent = $event->websiteIntent;
        $url = trim((string) ($intent['url'] ?? ''));
        if ($url === '') {
            return;
        }

        $existing = $this->websites->findByHost($url);
        if ($existing !== null) {
            $domain->update(['website_id' => $existing->id]);

            return;
        }

        $name = trim((string) ($intent['name'] ?? '')) ?: $domain->host();

        $website = $this->websites->create([
            'name' => $name,
            'url' => $url,
            'owner_contact_id' => $domain->owner_contact_id,
            'owner_user_id' => $domain->owner_user_id,
        ]);

        $domain->update(['website_id' => $website->id]);
    }
}
