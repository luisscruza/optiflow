<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case InvoicePayment = 'invoice_payment';
    case OtherIncome = 'other_income';

    /**
     * Get all payment types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::InvoicePayment->value => self::InvoicePayment->label(),
            self::OtherIncome->value => self::OtherIncome->label(),
        ];
    }

    /**
     * Get the display label for the payment type.
     */
    public function label(): string
    {
        return match ($this) {
            self::InvoicePayment => 'Pago a factura de cliente',
            self::OtherIncome => 'Otros ingresos',
        };
    }

    /**
     * Get the description for the payment type.
     */
    public function description(): string
    {
        return match ($this) {
            self::InvoicePayment => 'Cobro de una factura pendiente de un cliente',
            self::OtherIncome => 'Ingresos no relacionados a facturación (devolución de impuestos, intereses, etc.)',
        };
    }
}
