# Architecture

The codebase is organized around isolated **domains**. The point of this
document is to make it cheap to add the seventh domain six months from now
without bleeding state across the other six. Read this once before adding
anything new.

## Why isolated domains

Hive grows in two directions over time:
- **Wider**: more domains (Calendar today, maybe HR tomorrow).
- **Deeper**: existing domains grow features (Finance → full invoicing →
  fattura elettronica).

If domains can directly import each other's models, the second axis becomes
expensive — every cross-cutting feature touches every consumer. We pay a
modest one-time cost to keep them apart so the long-term cost stays flat.

## Folder layout

```
app/
├── Shared/                  ← cross-cutting kernel (Money, DTOs, traits)
└── Domains/
    └── {Name}/              ← e.g. Contacts, Websites, Finance
        ├── Models/
        ├── Filament/
        │   ├── Resources/
        │   ├── Pages/
        │   ├── Widgets/
        │   └── {Name}PanelPlugin.php   ← mounts Filament classes
        ├── Services/
        │   └── Public/      ← cross-domain entry points (return DTOs)
        ├── DTOs/
        ├── Events/
        ├── Listeners/
        ├── Jobs/
        ├── Http/
        │   └── Controllers/ ← only if domain exposes routes/webhooks
        ├── Database/
        │   ├── Migrations/
        │   ├── Factories/
        │   └── Seeders/
        ├── Enums/
        └── {Name}ServiceProvider.php
```

Translations live under `lang/{it,en}/{domain}/*.php`. Filament resource
labels read them via `__('contacts/labels.name')` etc.

## The hard rules

1. A domain MAY depend on `App\Shared\*` and Laravel core — nothing else.
2. A domain MAY NOT import another domain's `Models`, internal `Services`,
   `Database\*`, `Filament\*`, or `Enums`.
3. Cross-domain communication is one of:
   - **Domain Events** (fire-and-forget; queue when work is non-trivial)
   - **Public Service classes** under `Services/Public/` returning DTOs
4. No Eloquent relationships across domain boundaries. Use scalar foreign
   keys plus `XxxService::find($id)` returning a DTO.
5. **Exception, deliberately granted:** `Contact` IDs are referenced as
   scalar FKs from any domain, but no `belongsTo(Contact::class)` outside
   `App\Domains\Contacts`. Always go through `ContactsService::find($id)`.

If a feature seems to need a cross-domain Eloquent join, it usually means
the work belongs in a public-service method that does the heavy query
internally and returns a DTO/collection.

## Adding a new domain — step by step

Use the existing **Contacts** domain as the worked example. Each numbered
step lists the file(s) to create.

### 1. Folder skeleton

```
app/Domains/{Name}/
├── Models/
├── Filament/
│   ├── Resources/
│   └── {Name}PanelPlugin.php
├── Services/Public/
├── DTOs/
├── Events/
├── Listeners/
├── Database/
│   ├── Migrations/
│   ├── Factories/
│   └── Seeders/
├── Enums/
└── {Name}ServiceProvider.php
```

### 2. Migration — owns its own folder

`app/Domains/Contacts/Database/Migrations/2026_05_01_000001_create_contacts_table.php`

```php
Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    // …
    $this->jsonbOrJson($table, 'roles')->default(json_encode([]));
    $table->boolean('do_not_email')->default(false);
    $table->foreignId('owner_user_id')->nullable()
        ->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

`jsonb()` for Postgres production, `json()` fallback so SQLite-backed tests
keep working. NEVER drop a domain migration into `database/migrations/` —
that path is reserved for Laravel-core tables (users, jobs, cache).

### 3. Service provider

`app/Domains/Contacts/ContactsServiceProvider.php` — loads the domain's
migrations, maps factory namespaces, wires event listeners.

```php
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

    Factory::guessFactoryNamesUsing(/* domain-aware mapping */);

    Event::listen(ContactCreated::class, RegisterContactCreatedActivity::class);
}
```

Register the provider in `bootstrap/providers.php`:

```php
return [
    // …
    App\Domains\Contacts\ContactsServiceProvider::class,
];
```

### 4. Model

Put the model in `Domains/Contacts/Models/Contact.php`. Always:
- declare its own `$table` explicitly
- override `newFactory()` to point at the domain factory
- use `App\Shared\Concerns\BelongsToOwner` so `owner_user_id` is auto-set

### 5. Factory

`Domains/Contacts/Database/Factories/ContactFactory.php` extends
`Illuminate\Database\Eloquent\Factories\Factory`. Use the
`Faker\Factory::create('it_IT')` locale where shipping IT-flavored data.

### 6. Seeder

`Domains/Contacts/Database/Seeders/ContactsSeeder.php`. Call from the
top-level `DatabaseSeeder::run()`:

```php
$this->call([\App\Domains\Contacts\Database\Seeders\ContactsSeeder::class]);
```

Seeders should ship **realistic** Italian data — names, addresses, VAT
numbers — so the dashboard looks lived-in from the first `sail up`.

### 7. Filament plugin

`Domains/Contacts/Filament/ContactsPanelPlugin.php` implements
`Filament\Contracts\Plugin`. Inside `register()`:

```php
$panel->discoverResources(
    in: __DIR__.'/Resources',
    for: 'App\\Domains\\Contacts\\Filament\\Resources',
);
```

Then mount it in `App\Providers\Filament\AdminPanelProvider::panel()`:

```php
->plugins([
    SpatieLaravelTranslatablePlugin::make()->defaultLocales([...]),
    ContactsPanelPlugin::make(),
])
```

This is the single place the panel learns about a new domain.

### 8. Public service + DTO

If anyone outside the domain needs read access to your data, expose it via
`Services/Public/ContactsService.php` returning DTOs from `DTOs/`.

```php
public function find(int $id): ?ContactDTO
{
    $contact = Contact::query()->find($id);
    return $contact ? ContactDTO::fromModel($contact) : null;
}
```

Keep DTOs `final readonly` — they're snapshots, not live models.

### 9. Events

Domain events live in `Events/` and are simple value objects. Pass IDs and
scalars, NEVER Eloquent models — listeners should `find()` the model
themselves so the event can be queued safely.

```php
final class ContactFlaggedDoNotEmail
{
    use Dispatchable;

    public function __construct(
        public readonly int $contactId,
        public readonly ?string $reason = null,
    ) {}
}
```

### 10. Tests

Mirror the structure under `tests/Feature/Domains/{Name}/`:

- `XxxModelTest` — factory states, scopes, role/flag helpers
- `XxxServiceTest` — public-service contract, event dispatching
- `XxxResourceTest` — Filament smoke (Livewire component renders)

Plus integration tests for any external boundary the domain owns
(webhook controller, third-party API client).

## Money — never floats

Every monetary value lives in `app/Shared/ValueObjects/Money.php` —
integer cents + 3-letter ISO currency. Persist via two columns
(`amount_cents`, `currency`) and the `MoneyCast` cast. Build new amounts
through `Money::fromMajor("12.50", "EUR")` to avoid binary-float drift.

## Multi-tenancy readiness

Every domain table gets a nullable `owner_user_id` column from day one.
The `BelongsToOwner` trait auto-fills it on creating. When v1 grows into
v2 we make the column not-null and add a global scope; nothing in the
domain code itself will need to change.

## What this architecture costs you (and why it's worth it)

- **An extra hop** when one domain reads another's data — `find($id)`
  instead of an Eloquent join. In return: every domain can be modified,
  rewritten, or even replaced without touching the others.
- **Duplicate DTO definitions** when two domains need the same projection.
  In return: the projection is a contract you can version, not a leaky
  abstraction.
- **One more file** (the `PanelPlugin`) when adding a Filament-visible
  domain. In return: a panel that knows what's mounted by reading a
  single list, not by walking the filesystem.

If a constraint here genuinely fights you on a real feature, push back
in the PR rather than silently bending it. Architecture is an investment,
not a religion.
