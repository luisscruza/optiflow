<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

final class WelcomeEmailNotification extends Notification
{
    use Queueable;

    private string $domain;

    /**
     * Create a new notification instance.
     */
    public function __construct(#[SensitiveParameter] private string $password, string $subdomain)
    {
        $centralDomain = config()->string('tenancy.central_domain');

        $this->domain = $subdomain.'.'.$centralDomain;
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
        return (new MailMessage)
            ->subject('¡Bienvenido a '.config('app.name').'!')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('¡Bienvenido a '.config('app.name').'!')
            ->line('Puedes iniciar sesión en tu cuenta utilizando las siguientes credenciales:')
            ->line('Correo electrónico: '.$notifiable->email)
            ->line('Contraseña: '.$this->password)
            ->action('Iniciar sesión', tenant_route($this->domain, 'login'))
            ->line('Te recomendamos cambiar tu contraseña después de iniciar sesión por primera vez.')
            ->line('¡Gracias por unirte a nosotros!')
            ->salutation('Saludos cordiales, '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
