<?php

declare(strict_types=1);

namespace App\Domains\Websites\Services\Internal;

use App\Domains\Websites\Events\WebsiteWentDown;
use App\Domains\Websites\Models\Website;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Pings a single Website's URL and updates is_up / last_status_code /
 * last_pinged_at on the row. Treats 2xx and 3xx as up, anything else
 * (incl. timeout / DNS failure) as down.
 *
 * Tries HEAD first because it's cheap; falls back to GET if HEAD comes
 * back as 4xx/5xx (some servers refuse HEAD or only register 200 on
 * full GET).
 *
 * Internal — only the websites:ping command calls into this. External
 * callers read freshness off the model columns; no public service is
 * needed because nothing cross-domain consumes liveness.
 */
class WebsitePinger
{
    public function __construct(private readonly HttpFactory $http) {}

    public function ping(Website $website): void
    {
        if (empty($website->url)) {
            return;
        }

        $previousIsUp = $website->is_up;
        [$isUp, $statusCode] = $this->probe($website->url);

        $website->update([
            'is_up' => $isUp,
            'last_status_code' => $statusCode,
            'last_pinged_at' => now(),
        ]);

        // Fire the transition event only on a true up → down flip,
        // never on the first ping (previousIsUp was null) and never on
        // a sustained-down site.
        if ($previousIsUp === true && $isUp === false) {
            WebsiteWentDown::dispatch($website->id, $statusCode);
        }
    }

    /**
     * @return array{0: bool, 1: ?int}  [isUp, statusCode|null]
     */
    private function probe(string $url): array
    {
        try {
            $response = $this->http
                ->timeout(5)
                ->connectTimeout(3)
                ->withUserAgent('HiveCRM-Pinger/1.0')
                ->head($url);

            $code = $response->status();

            if ($code >= 200 && $code < 400) {
                return [true, $code];
            }

            // HEAD often gets blocked / mishandled. Retry once with GET.
            $response = $this->http
                ->timeout(5)
                ->connectTimeout(3)
                ->withUserAgent('HiveCRM-Pinger/1.0')
                ->get($url);

            $code = $response->status();

            return [$code >= 200 && $code < 400, $code];
        } catch (\Throwable) {
            // Connection refused, DNS failure, timeout — treated as down.
            return [false, null];
        }
    }
}
