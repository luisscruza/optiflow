<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static Builder<static>|ShareTemplate query()
 *
 * @mixin \Eloquent
 */
final class ShareTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ShareTemplateFactory> */
    use HasFactory;

    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => ShareTemplateEntity::class,
            'channel' => ShareTemplateChannel::class,
            'is_active' => 'bool',
        ];
    }
}
