<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\DTOs;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class DomainNameDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $host,
        public Registrar $registrar,
        public DomainStatus $status,
        public ?Carbon $registeredAt,
        public ?Carbon $expiresAt,
        public int $renewalPeriodMonths,
        public ?Money $renewalCost,
        public string $currency,
        public ?int $ownerContactId,
        public ?int $websiteId,
        public ?int $ownerUserId,
    ) {}

    public static function fromModel(DomainName $domain): self
    {
        return new self(
            id: $domain->id,
            name: (string) $domain->name,
            host: $domain->host(),
            registrar: $domain->registrar,
            status: $domain->status,
            registeredAt: $domain->registered_at,
            expiresAt: $domain->expires_at,
            renewalPeriodMonths: max(1, (int) $domain->renewal_period_months),
            renewalCost: $domain->renewalCost(),
            currency: $domain->currency ?: (string) config('app.currency', 'EUR'),
            ownerContactId: $domain->owner_contact_id,
            websiteId: $domain->website_id,
            ownerUserId: $domain->owner_user_id,
        );
    }
}
