<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasComments;
use App\Contracts\Commentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Comment $commentable
 * @property-read User|null $commentator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment query()
 *
 * @mixin \Eloquent
 */
final class Comment extends Model implements Commentable
{
    use HasComments;

    protected $fillable = [
        'comment',
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::deleting(function (self $model): void {
            $model->comments()->delete();
        });
    }

    /**
     * @return MorphTo<Comment, Model>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that made the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function commentator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
