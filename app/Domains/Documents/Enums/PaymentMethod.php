<?php

declare(strict_types=1);

namespace App\Domains\Documents\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Cash = 'cash';
    case Check = 'check';
    case Other = 'other';

    public function label(): string
    {
        return __('documents/labels.payment_method.'.$this->value);
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
