<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

final class WorkspaceUserAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<int, Workspace>  $workspaces
     */
    public function __construct(
        public Collection $workspaces,
        public User $assignedBy
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = config('app.url').'/login';
        $workspaceNames = $this->workspaces->pluck('name')->join(', ', ' y ');
        $isMultiple = $this->workspaces->count() > 1;

        return (new MailMessage)
            ->subject('Has sido agregado a '.($isMultiple ? 'nuevos workspaces' : 'un nuevo workspace'))
            ->greeting('¡Hola '.$notifiable->name.'!')
            ->line($this->assignedBy->name.' te ha agregado '.($isMultiple ? 'a los workspaces' : 'al workspace').': '.$workspaceNames.'.')
            ->line('Ya puedes acceder '.($isMultiple ? 'a estos workspaces' : 'a este workspace').' con tu cuenta existente.')
            ->action('Acceder', $loginUrl)
            ->line('Si tienes preguntas, contacta con '.$this->assignedBy->name.'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'workspaces' => $this->workspaces->map(fn ($workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ])->toArray(),
            'assigned_by' => $this->assignedBy->name,
        ];
    }
}
