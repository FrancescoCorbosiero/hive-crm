<?php

declare(strict_types=1);

namespace App\Domains\Leads\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return __('leads/status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Contacted => 'info',
            self::Qualified => 'warning',
            self::Proposal => 'primary',
            self::Won => 'success',
            self::Lost => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Won, self::Lost], true);
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }

    /** @return array<int, self> ordered as a sales pipeline */
    public static function pipeline(): array
    {
        return [self::New, self::Contacted, self::Qualified, self::Proposal];
    }
}
