<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactImportStatus: string
{
    case Pending = 'pending';
    case Mapping = 'mapping';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
