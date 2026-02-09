<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Enums\TaxType;
use App\Models\BankAccount;
use App\Models\CompanyDetail;
use App\Models\DocumentSubtype;
use App\Models\Quotation;
use App\Models\Salesman;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ContactSearch;
use App\Support\ProductSearch;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class CreateInvoiceFromQuotationController
{
    /**
     * Show the form for creating an invoice from a quotation.
     */
    public function __invoke(Request $request, Quotation $quotation, #[CurrentUser] User $user, ContactSearch $contactSearch, ProductSearch $productSearch): Response
    {
        abort_unless($user->can(Permission::InvoicesCreate), 403);

        $quotation->load(['contact', 'documentSubtype', 'items.product', 'items.taxes']);

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        $quotationItems = $quotation->items->map(fn ($item, $index) => [
            'id' => 'quotation_'.$index,
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'discount_rate' => $item->discount_rate ?? 0,
            'discount_amount' => $item->discount_amount ?? 0,
            'tax_rate' => $item->tax_rate ?? 0,
            'tax_amount' => $item->tax_amount ?? 0,
            'total' => $item->total,
            'taxes' => $item->taxes->map(function ($tax): array {
                /** @var \Illuminate\Database\Eloquent\Relations\Pivot|null $pivot */
                $pivot = $tax->getRelationValue('pivot');

                return [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'type' => $tax->type,
                    'rate' => $pivot?->getAttribute('rate'),
                    'amount' => $pivot?->getAttribute('amount'),
                ];
            })->values()->all(),
        ])->values()->all();

        $taxesGroupedByType = Tax::query()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn ($taxes, $type): array => [
                $type => [
                    'label' => TaxType::tryFrom($type)?->label() ?? $type,
                    'isExclusive' => TaxType::tryFrom($type)?->isExclusive() ?? false,
                    'taxes' => $taxes->toArray(),
                ],
            ])
            ->toArray();

        $salesmen = Salesman::query()
            ->orderBy('name')
            ->get();

        $documentSubtype = $this->getDefaultDocumentSubtype($currentWorkspace);

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'products' => $productSearch->findByIds($quotation->items->pluck('product_id')->all(), $currentWorkspace),
            'productSearchResults' => Inertia::optional(
                fn (): array => $productSearch->search((string) $request->string('product_search'), $currentWorkspace)
            ),
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
            'initialContact' => $quotation->contact ? $contactSearch->toOption($quotation->contact) : null,
            'customerSearchResults' => Inertia::optional(fn (): array => $contactSearch->searchCustomers((string) $request->string('contact_search'))),
            'defaultNote' => CompanyDetail::getByKey('terms_conditions'),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
            'taxesGroupedByType' => $taxesGroupedByType,
            'salesmen' => $salesmen,
            'fromQuotation' => [
                'id' => $quotation->id,
                'document_number' => $quotation->document_number,
                'contact_id' => $quotation->contact_id,
                'contact' => $quotation->contact,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => $quotation->due_date?->format('Y-m-d'),
                'payment_term' => $quotation->payment_term ?? 'manual',
                'notes' => $quotation->notes
                    ? "Convertida desde cotización #{$quotation->document_number}. ".$quotation->notes
                    : "Convertida desde cotización #{$quotation->document_number}.",
                'items' => $quotationItems,
                'subtotal' => (float) $quotation->subtotal_amount,
                'discount_total' => (float) $quotation->discount_amount,
                'tax_amount' => (float) $quotation->tax_amount,
                'total' => (float) $quotation->total_amount,
            ],
        ]);
    }

    private function getDefaultDocumentSubtype(?Workspace $workspace): ?DocumentSubtype
    {
        if ($workspace instanceof Workspace) {
            $workspacePreferred = $workspace->getPreferredDocumentSubtype();

            if ($workspacePreferred instanceof DocumentSubtype && $workspacePreferred->isValid()) {
                return $workspacePreferred;
            }
        }

        return DocumentSubtype::active()
            ->forInvoice()
            ->where('is_default', true)
            ->first();
    }
}
