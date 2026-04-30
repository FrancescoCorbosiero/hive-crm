<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationCounter extends Model
{
    protected $table = 'quotation_counters';

    protected $primaryKey = 'year';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['year', 'last_number'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
