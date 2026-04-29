<?php

declare(strict_types=1);

namespace App\Domains\Websites\DTOs;

use App\Domains\Websites\Models\Website;
use Carbon\Carbon;

final readonly class WebsiteDTO
{
    /**
     * @param  array<string,string>  $name  locale => translated name
     * @param  array<int,string>|null  $techStack
     */
    public function __construct(
        public int $id,
        public array $name,
        public string $url,
        public string $status,
        public ?int $ownerContactId,
        public ?array $techStack,
        public ?Carbon $subscriptionStartedAt,
        public ?Carbon $nextRenewalAt,
        public int $renewalPeriodMonths,
    ) {}

    public static function fromModel(Website $website): self
    {
        return new self(
            id: $website->id,
            name: $website->getTranslations('name'),
            url: $website->url,
            status: $website->status->value,
            ownerContactId: $website->owner_contact_id,
            techStack: $website->tech_stack ? array_values((array) $website->tech_stack) : null,
            subscriptionStartedAt: $website->subscription_started_at,
            nextRenewalAt: $website->next_renewal_at,
            renewalPeriodMonths: $website->renewal_period_months,
        );
    }

    public function nameForLocale(string $locale, string $fallback = 'en'): string
    {
        return $this->name[$locale] ?? $this->name[$fallback] ?? array_values($this->name)[0] ?? '';
    }
}
