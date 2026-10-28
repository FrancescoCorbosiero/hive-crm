<?php

declare(strict_types=1);

namespace App\Domains\Mail\Http\Controllers;

use App\Domains\Mail\Services\Internal\SesEventSync;
use App\Domains\Mail\Support\SnsMessageValidator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Handles Amazon SNS-signed webhooks carrying SES email events
 * (Bounce / Complaint / Delivery / Open / Click).
 *
 * SubscriptionConfirmation messages are auto-confirmed by hitting the
 * SubscribeURL — saves a manual ops step on first deploy.
 *
 * Notification messages: the outer SNS envelope is signature-verified
 * by SnsMessageValidator (which fetches AWS's public cert); the inner
 * `Message` field is the SES JSON, which we forward to SesEventSync.
 *
 * Always returns 200 on a verified signature, even for unrecognized
 * event types — SNS will keep retrying on non-2xx and we'd rather log
 * and move on than enter a thundering retry loop.
 */
class SesWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        SnsMessageValidator $validator,
        SesEventSync $sync,
    ): JsonResponse {
        $raw = $request->getContent();

        try {
            $message = $validator->validate($raw);
        } catch (Throwable $e) {
            Log::warning('mail.ses.signature_rejected', ['exception' => $e->getMessage()]);
            throw new AccessDeniedHttpException('Invalid SNS signature.');
        }

        $type = (string) ($message['Type'] ?? '');

        if ($type === 'SubscriptionConfirmation') {
            $url = (string) ($message['SubscribeURL'] ?? '');
            if ($url !== '') {
                Http::get($url);
            }

            return response()->json(['confirmed' => true], 200);
        }

        if ($type !== 'Notification') {
            return response()->json(['ignored' => $type], 200);
        }

        // SNS delivers at least once: a retried notification must not
        // bump the campaign counters again. MessageId is unique per
        // notification; Cache::add is atomic, so only the first wins.
        $seenKey = 'mail:sns:'.(string) ($message['MessageId'] ?? '');
        if (! Cache::add($seenKey, true, now()->addDays(2))) {
            return response()->json(['ignored' => 'duplicate'], 200);
        }

        $inner = json_decode((string) ($message['Message'] ?? ''), true);
        if (! is_array($inner)) {
            return response()->json(['ignored' => 'malformed_inner'], 200);
        }

        try {
            $sync->handle($inner);
        } catch (Throwable $e) {
            // Not processed after all: let SNS's retry through.
            Cache::forget($seenKey);

            throw $e;
        }

        return response()->json(null, 204);
    }
}
