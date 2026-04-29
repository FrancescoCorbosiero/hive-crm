<?php

declare(strict_types=1);

namespace App\Domains\Mail\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Stateless HMAC-signed unsubscribe tokens.
 *
 * URL form: `/unsubscribe/{contactId}.{expiresAt}.{sig}` — base64url-encoded.
 * Signing key is the application APP_KEY so we don't introduce a second
 * secret to rotate. TTL configurable via APP_UNSUBSCRIBE_TTL_DAYS
 * (defaults to 365 days).
 *
 * One-shot opt-out flow: if a token verifies, we mark do_not_email =
 * true. The token expiry exists to prevent indefinite-replay scenarios
 * where a leaked archive could still flip a customer's flag.
 */
final class UnsubscribeToken
{
    public static function for(int $contactId, ?int $ttlDays = null): string
    {
        $ttlDays ??= (int) env('APP_UNSUBSCRIBE_TTL_DAYS', 365);
        $expiresAt = Carbon::now()->addDays($ttlDays)->getTimestamp();

        $payload = $contactId.'.'.$expiresAt;
        $sig = hash_hmac('sha256', $payload, self::secret());

        return self::base64UrlEncode($payload.'.'.$sig);
    }

    /**
     * Verify and decode. Returns the contact id, or null on any
     * malformed / expired / tampered token.
     */
    public static function decode(string $token): ?int
    {
        $raw = self::base64UrlDecode($token);
        if ($raw === null) {
            return null;
        }

        $parts = explode('.', $raw);
        if (count($parts) !== 3) {
            return null;
        }

        [$contactId, $expiresAt, $sig] = $parts;

        if (! ctype_digit($contactId) || ! ctype_digit($expiresAt)) {
            return null;
        }

        if ((int) $expiresAt < Carbon::now()->getTimestamp()) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $contactId.'.'.$expiresAt, self::secret());
        if (! hash_equals($expectedSig, $sig)) {
            return null;
        }

        return (int) $contactId;
    }

    public static function url(int $contactId, ?int $ttlDays = null): string
    {
        return URL::to('/unsubscribe/'.self::for($contactId, $ttlDays));
    }

    private static function secret(): string
    {
        $key = (string) config('app.key');

        return str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7))
            : $key;
    }

    private static function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $input): ?string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($input, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
