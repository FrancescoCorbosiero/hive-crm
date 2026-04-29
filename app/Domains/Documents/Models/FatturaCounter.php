<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-year counter for fattura sequential numbering.
 *
 * Internal — never exposed across domain boundaries; FatturaService
 * is the only caller.
 */
class FatturaCounter extends Model
{
    protected $table = 'fattura_counters';

    protected $primaryKey = 'year';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'year',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
