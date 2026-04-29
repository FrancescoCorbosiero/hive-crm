<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * HMAC-SHA256 signature verification for Cal.com webhooks.
 *
 * Cal.com signs the raw request body with the shared secret and sends
 * the hex digest in `X-Cal-Signature-256`. We recompute and compare
 * with hash_equals() to defeat timing attacks.
 *
 * The route group already excludes `webhooks/*` from CSRF — this is the
 * door that replaces it for this endpoint.
 */
class VerifyCalcomSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.calcom.webhook_secret');
        if ($secret === '') {
            throw new AccessDeniedHttpException('Cal.com webhook secret is not configured.');
        }

        $header = (string) config('services.calcom.webhook_signature_header', 'X-Cal-Signature-256');
        $provided = (string) $request->header($header, '');

        if ($provided === '') {
            throw new AccessDeniedHttpException('Missing Cal.com webhook signature.');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        // Allow `sha256=<hex>` and bare hex forms — Cal.com has shipped
        // both over time.
        $candidate = str_starts_with($provided, 'sha256=')
            ? substr($provided, 7)
            : $provided;

        if (! hash_equals($expected, $candidate)) {
            throw new AccessDeniedHttpException('Invalid Cal.com webhook signature.');
        }

        return $next($request);
    }
}
