<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Models;

use App\Domains\Quotations\Database\Factories\QuotationFactory;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'quotations';

    protected $fillable = [
        'year',
        'number',
        'name',
        'client_contact_id',
        'lead_id',
        'issued_at',
        'valid_until',
        'lines',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'currency',
        'status',
        'document_id',
        'fattura_id',
        'notes',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'number' => 'integer',
            'issued_at' => 'date',
            'valid_until' => 'date',
            'lines' => AsArrayObject::class,
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'status' => QuotationStatus::class,
        ];
    }

    protected static function newFactory(): QuotationFactory
    {
        return QuotationFactory::new();
    }

    public function displayNumber(): string
    {
        return 'PREV-'.$this->year.'-'.str_pad((string) $this->number, 4, '0', STR_PAD_LEFT);
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
