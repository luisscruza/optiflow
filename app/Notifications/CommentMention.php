<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkflowJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CommentMention extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public User $mentioner
    ) {
        //
    }

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
        $target = $this->resolveTargetModel();

        return [
            'comment_id' => $this->comment->id,
            'mentioner_id' => $this->mentioner->id,
            'mentioner_name' => $this->mentioner->name,
            'comment_text' => $this->comment->comment,
            'commentable_type' => $target?->getMorphClass(),
            'commentable_id' => $target?->getKey(),
            'url' => $this->resolveUrl(),
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
            ->action('Ver comentario', $this->resolveUrl())
            ->line('Gracias por usar nuestra aplicación!');
    }

    private function resolveUrl(): string
    {
        $target = $this->resolveTargetModel();
        $hash = "#comment-{$this->comment->getKey()}";

        return match (true) {
            $target instanceof Contact => route('contacts.show', $target, false).$hash,
            $target instanceof Invoice => route('invoices.show', $target, false).$hash,
            $target instanceof WorkflowJob => route('workflows.jobs.show', [
                'workflow' => $target->workflow_id,
                'job' => $target->getKey(),
            ], false).$hash,
            default => route('notifications.index', absolute: false),
        };
    }

    private function resolveTargetModel(): ?Model
    {
        $target = $this->comment->commentable;

        while ($target instanceof Comment) {
            $target = $target->commentable;
        }

        return $target instanceof Model ? $target : null;
    }
}
