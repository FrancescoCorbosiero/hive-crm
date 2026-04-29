<?php

it('uses Italian as the default locale and English as fallback', function () {
    expect(config('app.locale'))->toBe('it');
    expect(config('app.fallback_locale'))->toBe('en');
});

it('runs in the Europe/Rome timezone', function () {
    expect(config('app.timezone'))->toBe('Europe/Rome');
});

it('uses EUR as the default currency', function () {
    expect(config('app.currency'))->toBe('EUR');
});

it('lists IT and EN as supported locales', function () {
    expect(config('app.supported_locales'))->toBe(['it', 'en']);
});

it('configures the s3 disk for Contabo with path-style endpoints', function () {
    expect(config('filesystems.disks.s3.use_path_style_endpoint'))->toBeTrue();
    expect(config('filesystems.disks.contabo.use_path_style_endpoint'))->toBeTrue();
});
