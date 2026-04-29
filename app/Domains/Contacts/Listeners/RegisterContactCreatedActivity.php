<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Listeners;

use App\Domains\Contacts\Events\ContactCreated;
use Illuminate\Support\Facades\Log;

/**
 * Reference listener wired by ContactsServiceProvider to demonstrate
 * the in-domain event/listener pattern. Replace with real behavior or
 * delete once Phase 2 lands a more useful sink.
 */
class RegisterContactCreatedActivity
{
    public function handle(ContactCreated $event): void
    {
        Log::info('Contact created', ['contact_id' => $event->contactId]);
    }
}
