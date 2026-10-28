<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Support\UnsubscribeToken;

/**
 * GET only shows a confirmation page: mail security scanners (Safe
 * Links & co.) fetch every link in a message on delivery, and an
 * unsubscribe-on-GET silently opts real customers out (RFC 8058 §1).
 * The unsubscribe itself happens on POST — the confirm button, or the
 * one-click List-Unsubscribe-Post request mail clients send.
 */
it('shows a confirmation page on GET without unsubscribing', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);
    $url = '/unsubscribe/'.UnsubscribeToken::for($contact->id);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertSee('method="POST"', escape: false);
    $response->assertSee($url, escape: false);
    expect($contact->fresh()->do_not_email)->toBeFalse();
});

it('unsubscribes on POST from the confirmation page', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $response = $this->post('/unsubscribe/'.UnsubscribeToken::for($contact->id));

    $response->assertOk();
    $response->assertSee(__('mail/unsubscribe.success_title'));
    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('accepts the RFC 8058 one-click POST', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $this->post('/unsubscribe/'.UnsubscribeToken::for($contact->id), ['List-Unsubscribe' => 'One-Click'])
        ->assertOk();

    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('is idempotent: posting the same valid token twice is fine', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);
    $url = '/unsubscribe/'.UnsubscribeToken::for($contact->id);

    $this->post($url)->assertOk();
    $this->post($url)->assertOk();

    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('renders the invalid view for a malformed token without flipping any flag', function (string $method) {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $response = $this->call($method, '/unsubscribe/garbage');

    $response->assertOk();
    $response->assertSee(__('mail/unsubscribe.invalid_title'));
    expect($contact->fresh()->do_not_email)->toBeFalse();
})->with(['GET', 'POST']);
