<?php

declare(strict_types=1);

use App\Http\Controllers\ProductController;
use App\Http\Controllers\WorkspaceContextController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('workspaces', WorkspaceController::class);

    Route::prefix('workspace-context')->name('workspace-context.')->group(function () {
        Route::patch('{workspace}', [WorkspaceContextController::class, 'update'])->name('update');
        Route::delete('{workspace}', [WorkspaceContextController::class, 'destroy'])->name('destroy');
    });

    Route::resource('products', ProductController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
