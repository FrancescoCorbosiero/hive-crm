<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Models;

use App\Domains\Contacts\Database\Factories\ContactFactory;
use App\Domains\Contacts\Enums\ContactRole;
use App\Shared\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Contact — a person or company touched by any part of the business.
 *
 * Roles are a flag-set: a single contact can be both a customer AND a
 * vendor, etc. Filter callers should use the `withRole()` scope rather
 * than asserting equality on `roles`.
 *
 * The `roles` column lives in jsonb on Postgres so we get index-friendly
 * containment queries. For SQLite test environments the jsonb method
 * falls back to TEXT and Eloquent reads/writes a JSON-encoded array.
 */
class Contact extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'roles', 'do_not_email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('contact');
    }

    protected $table = 'contacts';

    protected $fillable = [
        'name',
        'ragione_sociale',
        'email',
        'phone',
        'vat_number',
        'tax_code',
        'sdi_code',
        'pec_email',
        'address',
        'notes',
        'trello_board_url',
        'roles',
        'do_not_email',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'address' => AsArrayObject::class,
            'roles' => 'array',
            'do_not_email' => 'boolean',
        ];
    }

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    // ── Role flag-set helpers ──────────────────────────────────────────

    public function hasRole(ContactRole|string $role): bool
    {
        $value = $role instanceof ContactRole ? $role->value : $role;

        return in_array($value, $this->roles ?? [], true);
    }

    public function assignRole(ContactRole|string $role): self
    {
        $value = $role instanceof ContactRole ? $role->value : $role;
        $current = $this->roles ?? [];

        if (! in_array($value, $current, true)) {
            $current[] = $value;
            $this->roles = $current;
        }

        return $this;
    }

    public function removeRole(ContactRole|string $role): self
    {
        $value = $role instanceof ContactRole ? $role->value : $role;
        $this->roles = array_values(array_filter(
            $this->roles ?? [],
            fn (string $existing) => $existing !== $value,
        ));

        return $this;
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeWithRole($query, ContactRole|string $role)
    {
        $value = $role instanceof ContactRole ? $role->value : $role;

        // Postgres: jsonb @> '"customer"'::jsonb is index-friendly.
        // SQLite: emulate via LIKE on the JSON-encoded text.
        $driver = $query->getQuery()->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return $query->whereRaw('roles @> ?::jsonb', [json_encode([$value])]);
        }

        return $query->whereRaw("roles LIKE ?", ['%"'.$value.'"%']);
    }

    public function scopeMailable($query)
    {
        return $query->where('do_not_email', false)->whereNotNull('email');
    }

    // ── Cross-domain read-only relations ───────────────────────────────
    // Used by the Customer 360 view's relation managers. These point at
    // other domains' tables by foreign key — we deliberately keep them
    // as plain hasMany so the Contacts domain doesn't gain a hard
    // compile-time dependency on the other models. Reading is fine;
    // writing is still the responsibility of the owning domain.

    public function quotations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\Quotations\Models\Quotation::class, 'client_contact_id');
    }

    public function fatture(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\Documents\Models\Fattura::class, 'client_contact_id');
    }

    public function campaignRecipients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\Mail\Models\CampaignRecipient::class, 'contact_id');
    }

    /**
     * CalendarEvent has no contact FK — Cal.com only gives us the
     * attendee's email — so the join is on the email column on both
     * sides. Contacts without an email naturally get an empty set.
     */
    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\Calendar\Models\CalendarEvent::class, 'attendee_email', 'email');
    }
}
