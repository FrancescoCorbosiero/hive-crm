<?php

declare(strict_types=1);

namespace App\Domains\Documents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a Fattura row is allocated and persisted by
 * FatturaService::create(). Fires for both manual issuance (the
 * operator submitting the Filament form) and recurring auto-issue
 * (the daily scheduler), so cross-domain listeners can hook either
 * path with one subscription.
 *
 * The auto-spawn intents the operator may have opted into at the
 * create-form level (mark-as-paid, render PDF) are handled inline
 * by CreateFattura::afterCreate(), not via this event — they're
 * page-level concerns, not cross-domain. This event exists so
 * future listeners (e.g. Mail → send invoice, Calendar → due-date
 * reminder) can subscribe without modifying the create page.
 */
final class FatturaIssued
{
    use Dispatchable;

    public function __construct(public readonly int $fatturaId) {}
}
