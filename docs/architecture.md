# Architecture

The codebase is organized around isolated **domains** (thirteen today).
The point of this document is to make it cheap to add the next one without
bleeding state across the others. Read this once before adding anything
new.

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
        ├── CashFlow/        ← CashFlowSchedule implementations (optional)
        ├── Database/
        │   ├── Migrations/
        │   ├── Factories/
        │   └── Seeders/
        ├── Enums/
        └── {Name}ServiceProvider.php
```

Translations live under `lang/{it,en}/{domain}/*.php`. Filament resource
labels read them via `__('contacts/labels.name')` etc.

## The rules

Every domain has a **published language** — the only parts of it other
domains may touch:

| Published          | What it is                                               |
| ------------------ | -------------------------------------------------------- |
| `Services/Public/` | commands and queries; they return DTOs, ids or `Money`   |
| `DTOs/`            | `final readonly` snapshots                               |
| `Events/`          | facts that happened, carrying ids and scalars — no models |
| `Enums/`           | value types (statuses, frequencies, …)                   |

(The `create()` methods still return the new model, because Filament's
create pages need one; callers in other domains use only its `id`.)

Everything else — `Models/`, `Services/Internal/`, `Database/`,
`Filament/` — is internal to its domain. On top of that:

1. **The write side is strict.** Services, listeners, jobs, controllers,
   commands and cash-flow schedules use another domain only through its
   published language. To change another domain's data, call its public
   service (`PaymentsService::linkFinancialEntry()`) or react to its
   event — never `$theirModel->update()`.
2. **The read side composes.** Filament resources, pages and widgets may
   read other domains' models to display them (a table column, a
   dashboard widget, the Contact 360 relation managers) and may embed
   other domains' Filament components (`Contacts\Filament\Components\ContactPicker`).
   Writes from the UI still go through the owning domain's public
   service.
3. **Model relations stay inside the domain**, with two deliberate
   exceptions that exist to feed relation managers: `Contact` (Contact
   360) and `Website` (its domain names). Anywhere else, reference
   another domain by scalar FK and resolve it through its public
   service (`ContactsService::find($id)`).
4. **`App\Shared` depends on no domain.** It is the kernel every domain
   may use: `Money`, line-item maths (`Shared\Billing`), the cash-flow
   contract (`Shared\CashFlow`), Filament concerns.
5. **Events are announced by their owner.** To have another domain
   announce something, call its service — e.g.
   `WebsitesService::create($attributes, paymentIntent: …)` dispatches
   `WebsiteCreated`.
6. Seeders and factories are fixtures and may compose across domains.

`tests/Arch/BoundariesTest.php` enforces these rules (plus "no `env()` at
runtime" and "DTOs are final") as part of the normal test run.

If a feature seems to need a cross-domain Eloquent join on the write
side, the work usually belongs in a public-service method that does the
heavy query internally and returns a DTO/collection.

## Extension points

Some features need every domain's data without one domain reaching into
the others. Those are contracts in `App\Shared` that each domain
implements and registers by container tag.

**`Shared\CashFlow\CashFlowSchedule`** — money a domain expects to move:
recurring invoices, unpaid invoices, renewals, recurring expenses.
Implement `pending($until)` with the *same* step rule your domain applies
when the occurrence actually happens (e.g. `RecurringFattura::issueDateAfter()`),
then tag it in your service provider:

```php
$this->app->tag([HostingRenewalsSchedule::class], CashFlowSchedule::TAG);
```

Finance's `CashFlowProjectionService` sums whatever is registered;
nothing else needs to change.

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

The architecture suite (`tests/Arch`) discovers new domains by folder,
so the boundary rules apply to yours from its first commit. Mirror the
rest under `tests/Feature/Domains/{Name}/`:

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

## When a rule fights you

If a constraint here genuinely fights you on a real feature, push back
in the PR rather than silently bending it — and if the rule changes,
change `tests/Arch/BoundariesTest.php` in the same PR, so the doc and
the check never disagree. Architecture is an investment, not a religion.
