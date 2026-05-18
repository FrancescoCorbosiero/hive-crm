<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Listeners;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Carbon;

/**
 * When a Website is created and the operator opted into "also
 * register the matching domain", spawn the sibling DomainName
 * pointing at this Website via the scalar website_id back-link.
 *
 * Idempotent in two ways:
 *   - If a DomainName with the same host already exists, just point
 *     its website_id at the new Website (no duplicate is created).
 *     The host comparison reuses the normalisation logic from
 *     DomainName::host().
 *   - The `name` column carries a UNIQUE constraint, so even under
 *     duplicate event delivery only one row can ever materialise.
 *
 * Symmetric to the Websites\Listeners\CreateWebsiteFromDomainRegistration
 * listener that runs the reverse direction.
 */
class CreateDomainFromWebsite
{
    public function handle(WebsiteCreated $event): void
    {
        if ($event->domainIntent === null) {
            return;
        }

        $website = Website::query()->find($event->websiteId);
        if (! $website) {
            return;
        }

        $intent = $event->domainIntent;
        $name = $this->resolveName($intent, $website);
        if ($name === '') {
            return;
        }

        $existing = DomainName::query()->where('name', $name)->first();
        if ($existing !== null) {
            if ($existing->website_id === null) {
                $existing->update(['website_id' => $website->id]);
            }

            return;
        }

        $registrarValue = (string) ($intent['registrar'] ?? '');
        if ($registrarValue === '' || Registrar::tryFrom($registrarValue) === null) {
            return;
        }

        $registeredAt = isset($intent['registered_at']) && $intent['registered_at'] !== null
            ? Carbon::parse($intent['registered_at'])->toDateString()
            : ($website->subscription_started_at?->toDateString() ?? now()->toDateString());

        DomainName::query()->create([
            'name' => $name,
            'registrar' => $registrarValue,
            'status' => DomainStatus::Active->value,
            'registered_at' => $registeredAt,
            'renewal_period_months' => (int) ($intent['renewal_period_months']
                ?? $website->renewal_period_months
                ?? 12),
            'auto_renew' => true,
            'currency' => config('app.currency', 'EUR'),
            'owner_contact_id' => $website->owner_contact_id,
            'website_id' => $website->id,
            'owner_user_id' => $website->owner_user_id,
        ]);
    }

    /**
     * Normalise an arbitrary URL or hostname down to the canonical
     * registrable form (scheme / "www." / path stripped, lowercased).
     * Mirrors DomainName::host() so the auto-link matches cleanly.
     */
    private function resolveName(array $intent, Website $website): string
    {
        $raw = trim((string) ($intent['name_override'] ?? $website->url));
        if ($raw === '') {
            return '';
        }

        $value = mb_strtolower($raw);
        $host = str_contains($value, '://')
            ? (string) parse_url($value, PHP_URL_HOST)
            : (string) (parse_url('//'.$value, PHP_URL_HOST) ?: $value);

        return preg_replace('/^www\./', '', $host) ?? $host;
    }
}
