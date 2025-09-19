<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WorkspaceUserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Workspace $workspace,
        public User $invitedBy,
        public string $password
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

        return (new MailMessage)
            ->subject('Bienvenido a '.$this->workspace->name)
            ->greeting('¡Hola '.$notifiable->name.'!')
            ->line($this->invitedBy->name.' te ha creado una cuenta en '.$this->workspace->name.'.')
            ->line('**Tus credenciales de acceso:**')
            ->line('**Email:** '.$notifiable->email)
            ->line('**Contraseña temporal:** '.$this->password)
            ->line('Te recomendamos cambiar tu contraseña después de iniciar sesión.')
            ->action('Iniciar Sesión', $loginUrl)
            ->line('Si no esperabas recibir este correo, por favor ignóralo.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'invited_by' => $this->invitedBy->name,
        ];
    }
}
