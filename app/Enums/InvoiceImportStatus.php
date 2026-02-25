<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
