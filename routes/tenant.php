<?php

declare(strict_types=1);

use App\Http\Controllers\CompanyDetailsController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConvertQuotationToInvoiceController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CurrencyRateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentSubtypeController;
use App\Http\Controllers\DownloadInvoicePdfController;
use App\Http\Controllers\DownloadQuotationPdfController;
use App\Http\Controllers\InitialStockController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SetDefaultDocumentSubtypeController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WorkspaceContextController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceMemberRoleController;
use App\Http\Middleware\HasWorkspace;
use App\Http\Middleware\SetWorkspaceContext;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Route::get('/test', function () {
    //     $bank = BankAccount::onlyActive()->first();
    //     $bank->initial_balance_date = now();
    //     $bank->save();
    // });

    Route::get('/', function () {
        return redirect()->route('dashboard');
    })->name('home');

    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('{token}', [WorkspaceInvitationController::class, 'show'])->name('show');
        Route::post('{token}/accept', [WorkspaceInvitationController::class, 'update'])->name('accept');
        Route::post('{token}/decline', [WorkspaceInvitationController::class, 'destroy'])->name('decline');
    });

    Route::middleware(['auth', 'verified', SetWorkspaceContext::class])->group(function () {
        Route::resource('workspaces', WorkspaceController::class)->only(['index', 'store', 'update']);

        Route::prefix('workspace-context')->name('workspace-context.')->group(function () {
            Route::patch('{workspace}', [WorkspaceContextController::class, 'update'])->name('update');
            Route::delete('{workspace}', [WorkspaceContextController::class, 'destroy'])->name('destroy');
        });

        Route::post('comments', [\App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
        Route::patch('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

        Route::middleware(HasWorkspace::class)->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');

            Route::get('company-details', [CompanyDetailsController::class, 'edit'])->name('company-details.edit');
            Route::patch('company-details', [CompanyDetailsController::class, 'update'])->name('company-details.update');

            Route::resource('currencies', CurrencyController::class);
            Route::resource('currency-rates', CurrencyRateController::class);
            Route::post('currencies/{currency}/rates', [CurrencyRateController::class, 'store'])->name('currencies.rates.store');

            Route::resource('products', ProductController::class);

            // Product Import routes
            Route::resource('product-imports', ProductImportController::class)->except(['edit']);
            Route::post('product-imports/{product_import}/process', [ProductImportController::class, 'process'])->name('product-imports.process');
            Route::get('product-imports/template/download', [ProductImportController::class, 'template'])->name('product-imports.template');

            Route::resource('taxes', TaxController::class);

            Route::resource('contacts', ContactController::class);

            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/{invoice}/pdf', DownloadInvoicePdfController::class)->name('invoices.pdf');

            Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');

            Route::resource('quotations', QuotationController::class);
            Route::get('quotations/{quotation}/pdf', DownloadQuotationPdfController::class)->name('quotations.pdf');
            Route::post('quotations/{quotation}/convert-to-invoice', ConvertQuotationToInvoiceController::class)->name('quotations.convert-to-invoice');

            Route::resource('document-subtypes', DocumentSubtypeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
            Route::patch('document-subtypes/{documentSubtype}/set-default', SetDefaultDocumentSubtypeController::class)->name('document-subtypes.set-default');

            Route::get('inventory', function () {
                return Inertia::render('inventory/index');
            })->name('inventory.index');

            Route::resource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'create', 'store', 'show'])->parameters([
                'stock-adjustments' => 'product',
            ]);
            Route::resource('stock-transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show'])->parameters([
                'stock-transfers' => 'stockMovement',
            ]);
            Route::resource('initial-stock', InitialStockController::class)->only(['index', 'create', 'store']);

            Route::get('workspace/members', [WorkspaceMemberController::class, 'index'])->name('workspace.members.index');
            Route::patch('workspace/members/{member}/role', [WorkspaceMemberRoleController::class, 'update'])->name('workspace.members.update-role');
            Route::delete('workspace/members/{member}', [WorkspaceMemberController::class, 'destroy'])->name('workspace.members.destroy');

            Route::post('workspace/invitations', [WorkspaceInvitationController::class, 'store'])->name('workspace.invitations.store');
        });
    });

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
});
