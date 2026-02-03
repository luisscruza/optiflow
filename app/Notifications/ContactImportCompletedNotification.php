<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ContactImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactImport $contactImport,
        public int $imported,
        public int $errors
    ) {}

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
        $message = $this->errors === 0
            ? "Importación completada: {$this->imported} contactos importados."
            : "Importación finalizada con {$this->errors} errores. {$this->imported} contactos importados.";

        return [
            'message' => $message,
            'import_id' => $this->contactImport->id,
            'imported' => $this->imported,
            'errors' => $this->errors,
            'commentable_type' => 'contact_import',
            'commentable_id' => $this->contactImport->id,
        ];
    }

    public function broadcastType(): string
    {
        return 'contact_import.completed';
    }
}
