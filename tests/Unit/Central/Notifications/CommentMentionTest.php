<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Contact;
use App\Models\User;
use App\Notifications\CommentMention;

test('comment mention notification points replies to the root commentable page', function (): void {
    $mentioner = (new User())->forceFill(['id' => 50, 'name' => 'Ana Mentioner']);
    $contact = (new Contact())->forceFill(['id' => 123, 'name' => 'Acme']);
    $parentComment = (new Comment())->forceFill(['id' => 10, 'comment' => 'Comentario principal']);
    $replyComment = (new Comment())->forceFill(['id' => 20, 'comment' => 'Respuesta con mención']);

    $parentComment->setRelation('commentable', $contact);
    $replyComment->setRelation('commentable', $parentComment);

    $notification = new CommentMention($replyComment, $mentioner);
    $payload = $notification->toArray($mentioner);

    expect($payload['commentable_type'])->toBe('contact')
        ->and($payload['commentable_id'])->toBe(123)
        ->and($payload['url'])->toBe('/contacts/123#comment-20');
});
