<?php

it('switches the locale via the locale.switch route and persists it in the session', function () {
    $response = $this
        ->withSession(['locale' => 'it'])
        ->get('/locale/en');

    $response->assertRedirect();
    expect(session('locale'))->toBe('en');
});

it('rejects unsupported locales silently', function () {
    $response = $this
        ->withSession(['locale' => 'it'])
        ->get('/locale/de');

    $response->assertRedirect();
    expect(session('locale'))->toBe('it');
});
