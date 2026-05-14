<?php

declare(strict_types=1);

namespace App\Domains\Settings\Enums;

enum ProfileType: string
{
    case DittaIndividuale = 'ditta_individuale';
    case PersonaGiuridica = 'persona_giuridica';

    public function label(): string
    {
        return __('settings/profile.types.'.$this->value);
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
