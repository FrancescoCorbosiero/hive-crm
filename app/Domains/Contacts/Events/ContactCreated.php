<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ContactCreated
{
    use Dispatchable;

    public function __construct(public readonly int $contactId) {}
}
