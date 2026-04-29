<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\Database\Factories\FatturaFactory;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fattura extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'fatture';

    protected $fillable = [
        'year',
        'number',
        'client_contact_id',
        'issued_at',
        'lines',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'currency',
        'payment_status',
        'document_id',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'number' => 'integer',
            'issued_at' => 'date',
            'lines' => AsArrayObject::class,
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'payment_status' => PaymentStatus::class,
        ];
    }

    protected static function newFactory(): FatturaFactory
    {
        return FatturaFactory::new();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Display number with the standard "0001/2026" format.
     */
    public function displayNumber(): string
    {
        return str_pad((string) $this->number, 4, '0', STR_PAD_LEFT).'/'.$this->year;
    }

    public function subtotal(): Money
    {
        return new Money((int) $this->subtotal_cents, $this->currency);
    }

    public function vat(): Money
    {
        return new Money((int) $this->vat_cents, $this->currency);
    }

    public function total(): Money
    {
        return new Money((int) $this->total_cents, $this->currency);
    }
}
