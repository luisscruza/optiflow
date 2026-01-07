<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Models\BankAccount;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class CreateInvoiceFromQuotationController extends Controller
{
    /**
     * Show the form for creating an invoice from a quotation.
     */
    public function __invoke(Quotation $quotation, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesCreate), 403);

        $quotation->load(['contact', 'documentSubtype', 'items.product']);

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $customers = Contact::query()
            ->orderBy('name')
            ->get()
            ->map(function ($contact) {
                $phone = $contact->phone_primary ?? null;
                $contact->name = "{$contact->name}".($phone ? " ({$phone})" : '');

                return $contact;
            });

        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace): void {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace): void {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace): Product {
                $stock = $currentWorkspace ? $product->stocks->first() : null;
                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                unset($product->stocks);

                return $product;
            });

        $defaultDocumentSubtype = DocumentSubtype::active()->forInvoice()->where('is_default', true)->first();

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
        ])->values()->all();

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $defaultDocumentSubtype?->generateNCF(),
            'document_subtype_id' => $defaultDocumentSubtype?->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
            'defaultNote' => CompanyDetail::getByKey('terms_conditions'),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
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

    /**
     * Get stock status for a product.
     */
    private function getStockStatus(Product $product, ?ProductStock $stock): string
    {
        if (! $product->track_stock) {
            return 'not_tracked';
        }

        if (! $stock instanceof ProductStock || $stock->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($stock->quantity <= $stock->minimum_quantity) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
