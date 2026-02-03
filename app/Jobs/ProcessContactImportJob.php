<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ProcessContactImportAction;
use App\Models\ContactImport;
use App\Models\User;
use App\Notifications\ContactImportCompletedNotification;
use App\Notifications\ContactImportFailedNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessContactImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $contactImportId,
        public int $userId
    ) {}

    public function handle(ProcessContactImportAction $action): void
    {
        $contactImport = ContactImport::query()->find($this->contactImportId);
        $user = User::query()->find($this->userId);

        if (! $contactImport || ! $user) {
            return;
        }

        try {
            $result = $action->handle($contactImport, $user);

            $user->notify(new ContactImportCompletedNotification(
                contactImport: $contactImport,
                imported: (int) ($result['imported'] ?? 0),
                errors: (int) ($result['errors'] ?? 0)
            ));
        } catch (Exception $exception) {
            $contactImport->markAsFailed([
                [
                    'row' => 0,
                    'field' => 'general',
                    'message' => $exception->getMessage(),
                ],
            ]);

            $user->notify(new ContactImportFailedNotification(
                contactImport: $contactImport,
                error: $exception->getMessage()
            ));
        }
    }
}
