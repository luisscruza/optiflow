<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CommentMention extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public User $mentioner
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'mentioner_id' => $this->mentioner->id,
            'mentioner_name' => $this->mentioner->name,
            'comment_text' => $this->comment->comment,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'message' => "{$this->mentioner->name} te mencionó en un comentario",
        ];
    }

    /**
     * Get the mail representation of the notification (optional).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Te mencionaron en un comentario')
            ->line("{$this->mentioner->name} te mencionó en un comentario.")
            ->line($this->comment->comment)
            ->action('Ver comentario', url('/'))
            ->line('Gracias por usar nuestra aplicación!');
    }
}
