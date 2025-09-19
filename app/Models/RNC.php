<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RNC extends Model
{
    public $incrementing = false;

    protected $table = 'rncs';

    protected $primaryKey = 'identification';

    protected $keyType = 'string';
}
