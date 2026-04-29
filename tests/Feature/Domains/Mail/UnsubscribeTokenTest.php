<?php

use App\Domains\Mail\Support\UnsubscribeToken;

it('produces a token that decodes back to the contact id', function () {
    $token = UnsubscribeToken::for(42);

    expect(UnsubscribeToken::decode($token))->toBe(42);
});

it('rejects a tampered token', function () {
    $token = UnsubscribeToken::for(42);
    $tampered = substr($token, 0, -2).'AA';

    expect(UnsubscribeToken::decode($tampered))->toBeNull();
});

it('rejects a malformed token', function () {
    expect(UnsubscribeToken::decode('not-a-token'))->toBeNull();
});

it('rejects an expired token', function () {
    $token = UnsubscribeToken::for(42, ttlDays: 0);
    \Carbon\Carbon::setTestNow(now()->addMinute());

    expect(UnsubscribeToken::decode($token))->toBeNull();

    \Carbon\Carbon::setTestNow();
});

it('embeds the contact id in the URL', function () {
    $url = UnsubscribeToken::url(42);

    expect($url)->toContain('/unsubscribe/');
});
