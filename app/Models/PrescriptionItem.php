<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

final class PrescriptionItem extends Pivot
{
    public $timestamps = false;
}
