<?php

/**
 * The module rules from docs/architecture.md, enforced. A failure here
 * means a change reached into another domain's internals: use that
 * domain's published language instead (Services\Public, DTOs, Events,
 * Enums), or read the "When a rule fights you" section of the doc.
 */
$domains = array_map('basename', glob(dirname(__DIR__, 2).'/app/Domains/*', GLOB_ONLYDIR));

// What a domain keeps to itself.
$internalsOf = fn (string $domain): array => [
    "App\\Domains\\{$domain}\\Models",
    "App\\Domains\\{$domain}\\Services\\Internal",
    "App\\Domains\\{$domain}\\Database",
    "App\\Domains\\{$domain}\\Filament",
];

foreach ($domains as $domain) {
    $othersInternals = array_merge(...array_map($internalsOf, array_values(array_diff($domains, [$domain]))));

    arch("{$domain} uses other domains only through their published language")
        ->expect("App\\Domains\\{$domain}")
        ->not->toUse($othersInternals)
        // Read side: Filament composes across domains for display (writes
        // still go through the owner's public services). Fixtures and the
        // models' relations are covered separately.
        ->ignoring([
            "App\\Domains\\{$domain}\\Filament",
            "App\\Domains\\{$domain}\\Database",
            "App\\Domains\\{$domain}\\Models",
        ]);

    arch("{$domain} models relate to other domains' models only for relation managers")
        ->expect("App\\Domains\\{$domain}\\Models")
        ->not->toUse(array_values(array_filter($othersInternals, fn (string $ns) => str_ends_with($ns, '\\Models'))))
        // Contact 360 and the Website → domains tab are Filament relation
        // managers, which need Eloquent relations on the owning model.
        ->ignoring([
            'App\\Domains\\Contacts\\Models\\Contact',
            'App\\Domains\\Websites\\Models\\Website',
        ]);

    arch("{$domain} events carry ids and scalars, never models")
        ->expect("App\\Domains\\{$domain}\\Events")
        ->not->toUse("App\\Domains\\{$domain}\\Models");
}

arch('DTOs are final snapshots')
    ->expect(array_map(fn (string $domain) => "App\\Domains\\{$domain}\\DTOs", $domains))
    ->toBeFinal();

arch('the shared kernel depends on no domain')
    ->expect('App\\Shared')
    ->not->toUse('App\\Domains');

arch('runtime code reads config(), never env() — env() is null once the config is cached')
    ->expect('env')
    ->not->toBeUsedIn('App');

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
