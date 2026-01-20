<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCommentAction
{
    public function handle(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $comment->delete();
        });
    }
}
