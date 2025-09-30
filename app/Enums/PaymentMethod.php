<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case CreditCard = 'credit_card';
    case BankTransfer = 'bank_transfer';
    case MobilePayment = 'mobile_payment';
    case Transfer = 'transfer';
    case Other = 'other';

    /**
     * Get all the payment methods as an associative array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Cash->value => self::Cash->label(),
            self::Check->value => self::Check->label(),
            self::CreditCard->value => self::CreditCard->label(),
            self::BankTransfer->value => self::BankTransfer->label(),
            self::MobilePayment->value => self::MobilePayment->label(),
            self::Other->value => self::Other->label(),
        ];
    }

    /**
     * Get the human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Check => 'Check',
            self::CreditCard => 'Credit Card',
            self::BankTransfer => 'Bank Transfer',
            self::MobilePayment => 'Mobile Payment',
            self::Other => 'Other',
        };
    }
}
