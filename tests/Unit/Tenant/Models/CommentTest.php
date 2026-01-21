<?php

declare(strict_types=1);

use Database\Factories\CommentFactory;

test('to array', function (): void {
    $comment = CommentFactory::new()->create()->refresh();

    expect(array_keys($comment->toArray()))->toBe([
        'id',
        'commentable_type',
        'commentable_id',
        'comment',
        'user_id',
        'created_at',
        'updated_at',
        'edited_at',
    ]);
});
