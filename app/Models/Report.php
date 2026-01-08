<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportGroup;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'group',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'group' => ReportGroup::class,
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
