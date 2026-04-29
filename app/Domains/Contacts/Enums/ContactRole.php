<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Enums;

/**
 * A Contact may carry any combination of these roles. NOT a single-choice
 * enum — see `roles` jsonb column on the Contact model.
 */
enum ContactRole: string
{
    case Customer = 'customer';
    case Collaborator = 'collaborator';
    case Employer = 'employer';
    case Vendor = 'vendor';

    /** @return array<string,string> value => translated label */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function label(): string
    {
        return __('contacts/roles.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Customer => 'success',
            self::Collaborator => 'info',
            self::Employer => 'warning',
            self::Vendor => 'gray',
        };
    }
}
