<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Money — integer cents + ISO-4217 currency.
 *
 * Always store and pass money as Money. Never floats. Anywhere in the app.
 */
final readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency = 'EUR',
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be a 3-letter ISO-4217 code, got: {$currency}");
        }
    }

    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0, $currency);
    }

    public static function fromCents(int $cents, string $currency = 'EUR'): self
    {
        return new self($cents, $currency);
    }

    /**
     * Build from a "major unit" amount such as 12.50 or "1234.56".
     * Avoids float precision pitfalls by routing through string ops.
     */
    public static function fromMajor(int|float|string $amount, string $currency = 'EUR'): self
    {
        $string = is_string($amount) ? trim($amount) : (string) $amount;
        $string = str_replace(',', '.', $string);

        if (! preg_match('/^-?\d+(\.\d+)?$/', $string)) {
            throw new InvalidArgumentException("Invalid major amount: {$string}");
        }

        $negative = str_starts_with($string, '-');
        $string = ltrim($string, '-');

        [$whole, $fraction] = array_pad(explode('.', $string), 2, '0');
        $fraction = substr($fraction.'00', 0, 2);
        $cents = ((int) $whole) * 100 + (int) $fraction;

        return new self($negative ? -$cents : $cents, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->cents * $factor), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function toMajor(): float
    {
        return $this->cents / 100;
    }

    public function format(string $locale = 'it'): string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return number_format($this->toMajor(), 2, ',', '.').' '.$this->currency;
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->toMajor(), $this->currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}",
            );
        }
    }
}
