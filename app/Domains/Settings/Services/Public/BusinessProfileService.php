<?php

declare(strict_types=1);

namespace App\Domains\Settings\Services\Public;

use App\Domains\Settings\Models\BusinessProfile;

/**
 * Public surface of the Settings domain. The exporter and any
 * template that needs the owner's identity goes through here so the
 * data has a single source of truth.
 */
class BusinessProfileService
{
    /**
     * Return the singleton row, creating it on first call from any
     * env values that happen to be configured. Subsequent edits go
     * through the Filament page.
     */
    public function singleton(): BusinessProfile
    {
        $profile = BusinessProfile::query()->find(1);
        if ($profile) {
            return $profile;
        }

        // First boot: seed from env so existing FatturaPA flows keep
        // working without manual setup.
        return BusinessProfile::query()->create([
            'id' => 1,
            'type' => 'ditta_individuale',
            'denominazione' => (string) config('fattura.cedente.denominazione', ''),
            'codice_fiscale' => (string) config('fattura.cedente.codice_fiscale', ''),
            'partita_iva' => (string) config('fattura.cedente.partita_iva', ''),
            'regime_fiscale' => (string) config('fattura.cedente.regime_fiscale', 'RF19'),
            'natura_default' => (string) config('fattura.cedente.natura_default', 'N2.2'),
            'sede_indirizzo' => (string) config('fattura.cedente.sede.indirizzo', ''),
            'sede_civico' => (string) config('fattura.cedente.sede.numero_civico', ''),
            'sede_cap' => (string) config('fattura.cedente.sede.cap', ''),
            'sede_comune' => (string) config('fattura.cedente.sede.comune', ''),
            'sede_provincia' => (string) config('fattura.cedente.sede.provincia', ''),
            'sede_nazione' => (string) config('fattura.cedente.sede.nazione', 'IT'),
        ]);
    }

    /**
     * Return the same array shape that FatturaPaExporter previously
     * pulled from config('fattura.cedente'). Falls back to env when
     * the profile row hasn't been filled in yet — so the importer's
     * direction guard still works on a freshly-provisioned environment.
     *
     * @return array<string, mixed>
     */
    public function cedente(): array
    {
        $p = BusinessProfile::query()->find(1);

        $envFallback = [
            'codice_fiscale' => (string) config('fattura.cedente.codice_fiscale', ''),
            'partita_iva' => (string) config('fattura.cedente.partita_iva', ''),
            'denominazione' => (string) config('fattura.cedente.denominazione', ''),
            'regime_fiscale' => (string) config('fattura.cedente.regime_fiscale', 'RF19'),
            'natura_default' => (string) config('fattura.cedente.natura_default', 'N2.2'),
            'sede' => [
                'indirizzo' => (string) config('fattura.cedente.sede.indirizzo', ''),
                'numero_civico' => (string) config('fattura.cedente.sede.numero_civico', ''),
                'cap' => (string) config('fattura.cedente.sede.cap', ''),
                'comune' => (string) config('fattura.cedente.sede.comune', ''),
                'provincia' => (string) config('fattura.cedente.sede.provincia', ''),
                'nazione' => (string) config('fattura.cedente.sede.nazione', 'IT'),
            ],
        ];

        if (! $p) {
            return $envFallback;
        }

        $pick = fn (string $value, string $fallback): string => $value !== '' ? $value : $fallback;

        return [
            'codice_fiscale' => $pick((string) $p->codice_fiscale, $envFallback['codice_fiscale']),
            'partita_iva' => $pick((string) $p->partita_iva, $envFallback['partita_iva']),
            'denominazione' => $pick((string) $p->denominazione, $envFallback['denominazione']),
            'regime_fiscale' => $pick((string) $p->regime_fiscale, $envFallback['regime_fiscale']),
            'natura_default' => $pick((string) $p->natura_default, $envFallback['natura_default']),
            'sede' => [
                'indirizzo' => $pick((string) $p->sede_indirizzo, $envFallback['sede']['indirizzo']),
                'numero_civico' => $pick((string) $p->sede_civico, $envFallback['sede']['numero_civico']),
                'cap' => $pick((string) $p->sede_cap, $envFallback['sede']['cap']),
                'comune' => $pick((string) $p->sede_comune, $envFallback['sede']['comune']),
                'provincia' => $pick((string) $p->sede_provincia, $envFallback['sede']['provincia']),
                'nazione' => $pick((string) $p->sede_nazione, $envFallback['sede']['nazione']),
            ],
        ];
    }
}
