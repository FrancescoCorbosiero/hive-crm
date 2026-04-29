<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Http\Controllers;

use App\Domains\Calendar\Services\Internal\CalcomEventSync;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives Cal.com webhook deliveries.
 *
 * Signature verification is enforced by VerifyCalcomSignature middleware
 * applied at route registration. By the time this controller runs the
 * payload is trusted.
 *
 * Idempotency: CalcomEventSync upserts by Cal.com booking uid, so retried
 * deliveries collapse to a single row. We always return 204 on a valid
 * signature even if the payload is unrecognized — Cal.com retries on
 * non-2xx responses, and we don't want a malformed delivery to flood
 * the queue.
 */
class CalcomWebhookController extends Controller
{
    public function __invoke(Request $request, CalcomEventSync $sync): JsonResponse
    {
        $payload = $request->all();

        $sync->handlePayload($payload);

        return response()->json(null, 204);
    }
}
