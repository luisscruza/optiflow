<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Return all comments for this model.
     * Automatically eager loads nested comments recursively (infinite depth).
     *
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->with(['commentator', 'comments']);
    }

    /**
     * Attach a comment to this model.
     */
    public function comment(string $comment): Model
    {
        return $this->commentAsUser(auth()->user(), $comment);
    }

    /**
     * Attach a comment to this model as a specific user.
     */
    public function commentAsUser(?User $user, string $comment): Model
    {
        $comment = new Comment([
            'comment' => $comment,
            'user_id' => is_null($user) ? null : $user->getKey(),
            'commentable_id' => $this->getKey(),
            'commentable_type' => $this::class,
        ]);

        return $this->comments()->save($comment);
    }
}
