<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Exceptions\ReportableActionException;
use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class CreateExpenseAction
{
    /**
     * @param  array{workspace_id:int,contact_id:int,document_number:string,easyfactu_received_document_id?:string|null,issue_date:string,subtotal_amount:numeric-string|int|float,itbis_amount:numeric-string|int|float,isc_amount:numeric-string|int|float,withheld_itbis_amount:numeric-string|int|float,withheld_isr_amount:numeric-string|int|float,is_informal:bool,status:string,notes?:string|null,attachments?:array<UploadedFile>}  $data
     */
    public function handle(User $user, array $data): Expense
    {
        $workspace = $this->resolveWorkspace($user, (int) $data['workspace_id']);

        return DB::transaction(function () use ($workspace, $data): Expense {
            $expense = Expense::query()->create([
                'workspace_id' => $workspace->id,
                'contact_id' => $data['contact_id'],
                'document_number' => mb_trim($data['document_number']),
                'easyfactu_received_document_id' => $data['easyfactu_received_document_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'subtotal_amount' => $data['subtotal_amount'],
                'itbis_amount' => $data['itbis_amount'],
                'isc_amount' => $data['isc_amount'],
                'withheld_itbis_amount' => $data['withheld_itbis_amount'],
                'withheld_isr_amount' => $data['withheld_isr_amount'],
                'total_amount' => $this->calculateTotal($data),
                'is_informal' => $data['is_informal'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->addAttachments($expense, $data['attachments'] ?? []);

            return $expense->load(['contact', 'workspace', 'media']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calculateTotal(array $data): float
    {
        return round(
            (float) $data['subtotal_amount']
            + (float) $data['itbis_amount']
            + (float) $data['isc_amount']
            - (float) $data['withheld_itbis_amount']
            - (float) $data['withheld_isr_amount'],
            2,
        );
    }

    /**
     * @param  array<UploadedFile>  $attachments
     */
    private function addAttachments(Expense $expense, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $expense->addMedia($attachment)->toMediaCollection('attachments');
        }
    }

    private function resolveWorkspace(User $user, int $workspaceId): Workspace
    {
        $workspace = Workspace::query()->findOrFail($workspaceId);

        if ($user->can(Permission::ViewAllLocations)) {
            return $workspace;
        }

        if ($user->current_workspace_id !== $workspace->id) {
            throw new ReportableActionException('No tienes acceso para registrar gastos en esa sucursal.');
        }

        return $workspace;
    }
}
