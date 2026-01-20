<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCommentAction
{
    public function handle(Comment $comment, string $message): Comment
    {
        return DB::transaction(function () use ($comment, $message): Comment {
            $comment->update([
                'comment' => $message,
                'edited_at' => now(),
            ]);

            return $comment;
        });
    }
}
