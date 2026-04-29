<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Services\Public;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Thin REST client for Cal.com.
 *
 * Public surface: authenticated GET helpers used by the hourly sync
 * command. The webhook path is the primary ingestion route — this client
 * is the safety net for missed deliveries.
 *
 * Retry policy: 3 attempts, 250ms → 500ms → 1000ms exponential backoff,
 * retrying on connection errors and 5xx responses only.
 */
class CalcomService
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array<int, array<string,mixed>>  raw booking rows
     */
    public function getBookingsSince(CarbonInterface $since, int $limit = 100): array
    {
        $response = $this->client()->get('/bookings', [
            'afterStart' => $since->toIso8601ZuluString(),
            'limit' => $limit,
        ]);

        $response->throwIfClientError();
        $response->throwIfServerError();

        $body = $response->json();

        // Cal.com v2 wraps results in {data: [...]} — fall back to a
        // top-level array if the schema differs in dev sandboxes.
        return $body['data'] ?? (is_array($body) ? $body : []);
    }

    public function getBooking(string $uid): ?array
    {
        $response = $this->client()->get("/bookings/{$uid}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throwIfClientError();
        $response->throwIfServerError();

        return $response->json('data', $response->json());
    }

    private function client(): PendingRequest
    {
        $apiKey = (string) config('services.calcom.api_key');
        $baseUrl = rtrim((string) config('services.calcom.base_url', 'https://api.cal.com/v2'), '/');

        return $this->http
            ->withToken($apiKey)
            ->acceptJson()
            ->baseUrl($baseUrl)
            ->timeout(10)
            ->retry(
                times: 3,
                sleepMilliseconds: fn (int $attempt) => 250 * (2 ** ($attempt - 1)),
                when: function (\Throwable $exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response->serverError();
                    }

                    return false;
                },
                throw: false,
            )
            ->beforeSending(function ($request) {
                Log::debug('calcom.request', ['method' => $request->method(), 'url' => $request->url()]);
            });
    }
}
