<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case INITIAL = 'initial';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER = 'transfer';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case SALE = 'out';
    case PURCHASE = 'in';
    case RETURN_IN = 'return_in';
    case RETURN_OUT = 'return_out';
}
