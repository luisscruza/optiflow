<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ContactImportFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactImport $contactImport, public string $error) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Importación fallida: '.$this->error,
            'import_id' => $this->contactImport->id,
            'commentable_type' => 'contact_import',
            'commentable_id' => $this->contactImport->id,
        ];
    }

    public function broadcastType(): string
    {
        return 'contact_import.failed';
    }
}
