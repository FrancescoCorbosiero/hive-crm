<?php

declare(strict_types=1);

namespace App\Shared\Filament;

use Filament\Forms\Components\TextInput;

/**
 * Filament TextInput pre-configured for money editing.
 *
 * Storage stays integer-cents on the underlying column; the form shows
 * the major-unit decimal (€125.00) for editing. format/dehydrate hooks
 * handle the cents↔major conversion in both directions, so models and
 * service code never see a decimal — only the form does.
 *
 * Usage:
 *   MoneyInput::make('amount_cents')->required()
 *
 * The column name passed in is the actual cents column on the table.
 */
class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->step('0.01')
            ->prefix('€')
            ->minValue(0)
            ->formatStateUsing(fn ($state) => self::centsToMajor($state))
            ->dehydrateStateUsing(fn ($state) => self::majorToCents($state));
    }

    /** Cents (int) → "125.00" string for the form input. */
    public static function centsToMajor(mixed $cents): ?string
    {
        return $cents !== null
            ? number_format(((int) $cents) / 100, 2, '.', '')
            : null;
    }

    /** "125.00" or "125,00" → 12500 (int) for the DB. */
    public static function majorToCents(mixed $major): ?int
    {
        if ($major === null || $major === '') {
            return null;
        }

        return (int) round((float) str_replace(',', '.', (string) $major) * 100);
    }
}
