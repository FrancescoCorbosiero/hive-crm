<?php

declare(strict_types=1);

namespace App\Shared\Casts;

use App\Shared\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts an integer cents column + a sibling currency column into a Money
 * value object.
 *
 * Usage on a model:
 *   protected function casts(): array
 *   {
 *       return [
 *           'amount' => MoneyCast::class.':amount_cents,currency',
 *       ];
 *   }
 *
 * The cast reads/writes two underlying columns. Assign with a Money
 * instance; reads return a Money instance.
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(
        protected ?string $amountColumn = null,
        protected ?string $currencyColumn = null,
    ) {
        $this->amountColumn ??= 'amount_cents';
        $this->currencyColumn ??= 'currency';
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $cents = $attributes[$this->amountColumn] ?? null;

        if ($cents === null) {
            return null;
        }

        $currency = $attributes[$this->currencyColumn] ?? config('app.currency', 'EUR');

        return new Money((int) $cents, (string) $currency);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [
                $this->amountColumn => null,
                $this->currencyColumn => $attributes[$this->currencyColumn] ?? null,
            ];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException(
                'Money cast expects a '.Money::class.' instance, got: '.get_debug_type($value),
            );
        }

        return [
            $this->amountColumn => $value->cents,
            $this->currencyColumn => $value->currency,
        ];
    }
}
