<?php

declare(strict_types=1);

namespace App\Domains\Documents\Enums;

enum DocumentCategory: string
{
    case Fattura = 'fattura';
    case Contract = 'contract';
    case Receipt = 'receipt';
    case Other = 'other';

    public function label(): string
    {
        return __('documents/labels.category.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Fattura => 'success',
            self::Contract => 'info',
            self::Receipt => 'warning',
            self::Other => 'gray',
        };
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
