<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Console\Commands;

use App\Domains\Calendar\Services\Internal\CalcomEventSync;
use App\Domains\Calendar\Services\Public\CalcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Hourly fallback: re-pulls recent bookings from Cal.com and upserts
 * any we don't already have. Catches webhook deliveries we might have
 * missed (network blip, our service down, signature change).
 *
 * Idempotent: same booking processed twice = one DB row, thanks to
 * unique (cal_event_id) and upsert semantics.
 *
 * Scheduled in routes/console.php — see hourly() Schedule entry.
 */
class SyncCalcomEventsCommand extends Command
{
    protected $signature = 'calcom:sync {--hours=2 : How far back to pull bookings}';

    protected $description = 'Re-pull recent Cal.com bookings as a fallback for missed webhooks.';

    public function handle(CalcomService $client, CalcomEventSync $sync): int
    {
        $hours = (int) $this->option('hours');
        $since = now()->subHours($hours);

        if (! config('services.calcom.api_key')) {
            $this->warn('CALCOM_API_KEY not set — skipping sync.');

            return self::SUCCESS;
        }

        try {
            $bookings = $client->getBookingsSince($since);
        } catch (\Throwable $e) {
            Log::error('calcom.sync.failed', ['exception' => $e->getMessage()]);
            $this->error('Cal.com sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $count = 0;
        foreach ($bookings as $booking) {
            // Wrap each row in a "payload" envelope the same way webhooks
            // arrive, so the sync helper has one canonical shape.
            $sync->handlePayload(['triggerEvent' => 'BOOKING_SYNC', 'payload' => $booking]);
            $count++;
        }

        $this->info("Synced {$count} booking(s) from Cal.com.");

        return self::SUCCESS;
    }
}
