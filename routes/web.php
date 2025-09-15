<?php

declare(strict_types=1);

use App\Http\Controllers\ClientController;
use App\Http\Controllers\InitialStockController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WorkspaceContextController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\SetWorkspaceContext;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified', SetWorkspaceContext::class])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('workspaces', WorkspaceController::class);

    Route::prefix('workspace-context')->name('workspace-context.')->group(function () {
        Route::patch('{workspace}', [WorkspaceContextController::class, 'update'])->name('update');
        Route::delete('{workspace}', [WorkspaceContextController::class, 'destroy'])->name('destroy');
    });

    Route::resource('products', ProductController::class);

    Route::resource('taxes', TaxController::class);

    Route::resource('clients', ClientController::class);

    Route::resource('suppliers', SupplierController::class);

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
    Route::resource('initial-stock', InitialStockController::class)->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
