<?php

declare(strict_types=1);

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentSubtypeController;
use App\Http\Controllers\InitialStockController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SetDefaultDocumentSubtypeController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WorkspaceContextController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\HasWorkspace;
use App\Http\Middleware\SetWorkspaceContext;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified', SetWorkspaceContext::class])->group(function () {
    Route::resource('workspaces', WorkspaceController::class);

    Route::prefix('workspace-context')->name('workspace-context.')->group(function () {
        Route::patch('{workspace}', [WorkspaceContextController::class, 'update'])->name('update');
        Route::delete('{workspace}', [WorkspaceContextController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(HasWorkspace::class)->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');

        // Configuration page
        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');

        Route::resource('products', ProductController::class);

        Route::resource('taxes', TaxController::class);

        Route::resource('contacts', ContactController::class);

        // Invoices - Document management
        Route::resource('invoices', InvoiceController::class);

        // Document Subtypes (Numeraciones) - NCF management
        Route::resource('document-subtypes', DocumentSubtypeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('document-subtypes/{documentSubtype}/set-default', SetDefaultDocumentSubtypeController::class)->name('document-subtypes.set-default');

        // Inventory overview page
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

    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
