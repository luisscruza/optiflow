<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConvertReceivedDocumentToExpenseAction;
use App\Enums\Permission;
use App\Exceptions\EasyFactuException;
use App\Exceptions\ReportableActionException;
use App\Models\User;
use App\Services\EasyFactuService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class ConvertReceivedDocumentToExpenseController
{
    public function __invoke(
        string $receivedDocument,
        #[CurrentUser] User $user,
        EasyFactuService $easyFactu,
        ConvertReceivedDocumentToExpenseAction $action,
    ): RedirectResponse {
        abort_unless($user->can(Permission::ElectronicInvoicingView), 403);
        abort_unless($user->can(Permission::ExpensesCreate), 403);

        try {
            $response = $easyFactu->getReceivedDocument($receivedDocument);
            $document = $response['document'] ?? null;

            if (! is_array($document)) {
                return redirect()->route('electronic-invoicing.received.index')
                    ->with('error', 'No se encontró el documento recibido solicitado.');
            }

            $result = $action->handle($user, $document);
        } catch (EasyFactuException|ReportableActionException $exception) {
            return redirect()->route('electronic-invoicing.received.show', ['receivedDocument' => $receivedDocument])
                ->with('error', $exception->getMessage());
        }

        $message = $result['created']
            ? ($result['supplier_created']
                ? 'Gasto creado correctamente y suplidor registrado automáticamente.'
                : 'Gasto creado correctamente.')
            : 'Este documento ya había sido convertido en gasto.';

        $flashPayload = [
            'message' => $message,
            'action' => [
                'label' => 'Ver documento recibido',
                'href' => route('electronic-invoicing.received.show', ['receivedDocument' => $receivedDocument]),
            ],
        ];

        return redirect()->route('expenses.show', [
            'expense' => $result['expense'],
            'receivedDocument' => $receivedDocument,
        ])
            ->with($result['created'] ? 'success' : 'warning', $flashPayload);
    }
}
