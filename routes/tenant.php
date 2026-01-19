<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BulkDownloadInvoicePdfController;
use App\Http\Controllers\BulkDownloadQuotationPdfController;
use App\Http\Controllers\BusinessUserController;
use App\Http\Controllers\BusinessUserInvitationController;
use App\Http\Controllers\BusinessUserWorkspaceController;
use App\Http\Controllers\BusinessUserWorkspaceRoleController;
use App\Http\Controllers\CompanyDetailsController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConvertQuotationToInvoiceController;
use App\Http\Controllers\CreateInvoiceFromQuotationController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CurrencyRateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\DocumentSubtypeController;
use App\Http\Controllers\DownloadInvoicePdfController;
use App\Http\Controllers\DownloadPrescriptionController;
use App\Http\Controllers\DownloadQuotationPdfController;
use App\Http\Controllers\GlobalRoleController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InitialStockController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\QuickProductCreate;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportGroupController;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\SetDefaultDocumentSubtypeController;
use App\Http\Controllers\SetWorkspacePreferredDocumentSubtypeController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StreamInvoicePdfController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowJobController;
use App\Http\Controllers\WorkflowJobStageController;
use App\Http\Controllers\WorkflowStageController;
use App\Http\Controllers\WorkspaceContextController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceMemberRoleController;
use App\Http\Controllers\WorkspaceRoleController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\HasWorkspace;
use App\Http\Middleware\SetWorkspaceContext;
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
])->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

    Route::prefix('invitations')->name('invitations.')->group(function (): void {
        Route::get('{token}', [WorkspaceInvitationController::class, 'show'])->name('show');
        Route::post('{token}/accept', [WorkspaceInvitationController::class, 'update'])->name('accept');
        Route::post('{token}/decline', [WorkspaceInvitationController::class, 'destroy'])->name('decline');
    });

    Route::middleware(['auth'])->group(function (): void {
        Route::get('new-password', [PasswordChangeController::class, 'edit'])->name('password.new');
        Route::post('new-password', [PasswordChangeController::class, 'update'])->name('password.new.update');
    });

    Route::middleware(['auth', 'verified', SetWorkspaceContext::class, EnsurePasswordChanged::class])->group(function (): void {
        Route::resource('workspaces', WorkspaceController::class)->only(['index', 'store', 'update']);

        Route::prefix('workspace-context')->name('workspace-context.')->group(function (): void {
            Route::patch('{workspace}', [WorkspaceContextController::class, 'update'])->name('update');
            Route::delete('{workspace}', [WorkspaceContextController::class, 'destroy'])->name('destroy');
        });

        Route::post('comments', [App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
        Route::patch('comments/{comment}', [App\Http\Controllers\CommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

        // Business-wide user management (not scoped to workspace)
        Route::get('business/users', [BusinessUserController::class, 'index'])->name('business.users.index');
        Route::post('business/users/invite', [BusinessUserInvitationController::class, 'store'])->name('business.users.invite');
        Route::patch('business/users/{user}/workspaces/{workspace}/roles', [BusinessUserWorkspaceRoleController::class, 'update'])->name('business.users.workspaces.roles.update');
        Route::post('business/users/{user}/workspaces', [BusinessUserWorkspaceController::class, 'store'])->name('business.users.workspaces.store');
        Route::delete('business/users/{user}/workspaces/{workspace}', [BusinessUserWorkspaceController::class, 'destroy'])->name('business.users.workspaces.destroy');

        // Business-wide role management (global roles synced across all workspaces)
        Route::get('business/roles', [GlobalRoleController::class, 'index'])->name('business.roles.index');
        Route::post('business/roles', [GlobalRoleController::class, 'store'])->name('business.roles.store');
        Route::patch('business/roles/{roleName}', [GlobalRoleController::class, 'update'])->name('business.roles.update');
        Route::delete('business/roles/{roleName}', [GlobalRoleController::class, 'destroy'])->name('business.roles.destroy');
        Route::post('business/roles/{roleName}/sync', [GlobalRoleController::class, 'sync'])->name('business.roles.sync');

        Route::middleware(HasWorkspace::class)->group(function (): void {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('/dashboard/layout', [DashboardLayoutController::class, 'store'])->name('dashboard.layout.store');

            Route::get('activities', [ActivityLogController::class, 'index'])->name('activities.index');
            Route::get('activities/{model}/{id}', [ActivityLogController::class, 'show'])->name('activities.show');

            Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');

            Route::get('company-details', [CompanyDetailsController::class, 'edit'])->name('company-details.edit');
            Route::patch('company-details', [CompanyDetailsController::class, 'update'])->name('company-details.update');

            Route::resource('currencies', CurrencyController::class);
            Route::resource('currency-rates', CurrencyRateController::class);
            Route::post('currencies/{currency}/rates', [CurrencyRateController::class, 'store'])->name('currencies.rates.store');

            Route::resource('products', ProductController::class);
            Route::post('products/quick-create', QuickProductCreate::class)->name('products.quick-create');

            // Product Import routes
            Route::resource('product-imports', ProductImportController::class)->except(['edit']);
            Route::post('product-imports/{product_import}/process', [ProductImportController::class, 'process'])->name('product-imports.process');
            Route::get('product-imports/template/download', [ProductImportController::class, 'template'])->name('product-imports.template');

            Route::resource('taxes', TaxController::class);

            Route::resource('contacts', ContactController::class);

            Route::resource('salesmen', SalesmanController::class)->except(['show']);

            Route::resource('bank-accounts', BankAccountController::class);

            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/create/quotation/{quotation}', CreateInvoiceFromQuotationController::class)->name('invoices.create-from-quotation');
            Route::get('invoices/{invoice}/pdf', DownloadInvoicePdfController::class)->name('invoices.pdf');
            Route::get('invoices/{invoice}/pdf-stream', StreamInvoicePdfController::class)->name('invoices.pdf-stream');

            Route::post('invoices/bulk/pdf', BulkDownloadInvoicePdfController::class)->name('invoices.bulk.pdf');

            Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
            Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
            Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
            Route::patch('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
            Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

            Route::resource('quotations', QuotationController::class);
            Route::get('quotations/{quotation}/pdf', DownloadQuotationPdfController::class)->name('quotations.pdf');
            Route::post('quotations/bulk/pdf', BulkDownloadQuotationPdfController::class)->name('quotations.bulk.pdf');
            Route::post('quotations/{quotation}/convert-to-invoice', ConvertQuotationToInvoiceController::class)->name('quotations.convert-to-invoice');

            Route::resource('document-subtypes', DocumentSubtypeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
            Route::patch('document-subtypes/{documentSubtype}/set-default', SetDefaultDocumentSubtypeController::class)->name('document-subtypes.set-default');
            Route::patch('document-subtypes/{documentSubtype}/workspace/{workspace}/set-preferred', SetWorkspacePreferredDocumentSubtypeController::class)->name('document-subtypes.set-workspace-preferred');

            Route::get('inventory', fn () => Inertia::render('inventory/index'))->name('inventory.index');

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

            Route::get('workspace/roles', [WorkspaceRoleController::class, 'index'])->name('workspace.roles.index');
            Route::post('workspace/roles', [WorkspaceRoleController::class, 'store'])->name('workspace.roles.store');
            Route::patch('workspace/roles/{role}', [WorkspaceRoleController::class, 'update'])->name('workspace.roles.update');
            Route::delete('workspace/roles/{role}', [WorkspaceRoleController::class, 'destroy'])->name('workspace.roles.destroy');

            Route::post('workspace/invitations', [WorkspaceInvitationController::class, 'store'])->name('workspace.invitations.store');

            Route::resource('prescriptions', PrescriptionController::class);
            Route::get('prescriptions/{prescription}/pdf', DownloadPrescriptionController::class)->name('prescriptions.pdf');

            // Reports routes
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/group/{group}', [ReportGroupController::class, 'show'])->name('reports.group');
            Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
            Route::get('reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');

            // Workflow routes (Kanban for lens processing)
            Route::resource('workflows', WorkflowController::class);
            Route::post('workflows/{workflow}/stages', [WorkflowStageController::class, 'store'])->name('workflows.stages.store');
            Route::patch('workflows/{workflow}/stages/{stage}', [WorkflowStageController::class, 'update'])->name('workflows.stages.update');
            Route::delete('workflows/{workflow}/stages/{stage}', [WorkflowStageController::class, 'destroy'])->name('workflows.stages.destroy');
            Route::get('workflows/{workflow}/jobs/{job}', [WorkflowJobController::class, 'show'])->name('workflows.jobs.show');
            Route::post('workflows/{workflow}/jobs', [WorkflowJobController::class, 'store'])->name('workflows.jobs.store');
            Route::patch('workflows/{workflow}/jobs/{job}', [WorkflowJobController::class, 'update'])->name('workflows.jobs.update');
            Route::patch('workflows/{workflow}/jobs/{job}/move', [WorkflowJobStageController::class, 'update'])->name('workflows.jobs.move');
            Route::delete('workflows/{workflow}/jobs/{job}', [WorkflowJobController::class, 'destroy'])->name('workflows.jobs.destroy');

            Route::post('impersonate/{user}', [ImpersonationController::class, 'store']);
            Route::delete('impersonate', [ImpersonationController::class, 'destroy']);
        });
    });

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
});
