<?php

declare(strict_types=1);

namespace App\Domains\Settings\Models;

use App\Domains\Settings\Enums\ProfileType;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

/**
 * Owner identity. Singleton — exactly one row (id = 1) is maintained
 * by BusinessProfileService::singleton().
 */
class BusinessProfile extends Model
{
    protected $table = 'business_profile';

    protected $fillable = [
        'type',
        'denominazione',
        'nome',
        'cognome',
        'codice_fiscale',
        'partita_iva',
        'regime_fiscale',
        'natura_default',
        'sede_indirizzo',
        'sede_civico',
        'sede_cap',
        'sede_comune',
        'sede_provincia',
        'sede_nazione',
        'email',
        'pec_email',
        'phone',
        'website_url',
        'bank_accounts',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProfileType::class,
            'bank_accounts' => AsArrayObject::class,
        ];
    }

    /**
     * Return the primary bank account row, if any. Used by the
     * FatturaPA exporter to populate DatiPagamento.
     *
     * @return array<string,mixed>|null
     */
    public function primaryBankAccount(): ?array
    {
        $accounts = (array) ($this->bank_accounts?->toArray() ?? []);
        foreach ($accounts as $account) {
            if (! empty($account['is_primary'])) {
                return $account;
            }
        }

        return $accounts[0] ?? null;
    }
}
