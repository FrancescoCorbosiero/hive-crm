<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Support\UnsubscribeToken;

it('flips do_not_email when a valid token is presented', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $response = $this->get('/unsubscribe/'.UnsubscribeToken::for($contact->id));

    $response->assertOk();
    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('renders the invalid view for a malformed token without flipping any flag', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $response = $this->get('/unsubscribe/garbage');

    $response->assertOk();
    expect($contact->fresh()->do_not_email)->toBeFalse();
});

it('is idempotent: hitting the same valid token twice is fine', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);
    $url = '/unsubscribe/'.UnsubscribeToken::for($contact->id);

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('also accepts the one-click POST form (RFC 8058)', function () {
    $contact = Contact::factory()->create(['do_not_email' => false]);

    $response = $this->post('/unsubscribe/'.UnsubscribeToken::for($contact->id));

    $response->assertOk();
    expect($contact->fresh()->do_not_email)->toBeTrue();
});
