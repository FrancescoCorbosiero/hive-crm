<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a contact is flagged do_not_email — either by an explicit
 * admin action, an unsubscribe link click, or a bounce/complaint webhook.
 *
 * The Mail domain listens for this in Phase 5 to scrub pending campaign
 * recipients.
 */
final class ContactFlaggedDoNotEmail
{
    use Dispatchable;

    public function __construct(
        public readonly int $contactId,
        public readonly ?string $reason = null,
    ) {}
}
