<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

/**
 * Macro-categories of the typical Italian SMB landscape a web-design
 * shop sells to. Deliberately coarser than the codice ATECO so the
 * filter UI stays usable (17 buckets vs ~800).
 */
enum BusinessCategory: string
{
    case Intrattenimento = 'intrattenimento';
    case Turismo = 'turismo';
    case Benessere = 'benessere';
    case Ristorazione = 'ristorazione';
    case Artigianato = 'artigianato';
    case Commercio = 'commercio';
    case Edilizia = 'edilizia';
    case Animali = 'animali';
    case Immobiliare = 'immobiliare';
    case Industria = 'industria';
    case Sanita = 'sanita';
    case Professionisti = 'professionisti';
    case Istruzione = 'istruzione';
    case Moda = 'moda';
    case Tecnologia = 'tecnologia';
    case NonProfit = 'non_profit';
    case AltriServizi = 'altri_servizi';

    public function label(): string
    {
        return __('leads/business_category.'.$this->value);
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
