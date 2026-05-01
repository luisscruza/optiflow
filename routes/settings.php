<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\EasyFactuSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function (): void {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', fn () => Inertia::render('settings/appearance'))->name('appearance');

    // Electronic invoicing settings
    Route::get('settings/electronic-invoicing', [EasyFactuSettingsController::class, 'show'])->name('settings.electronic-invoicing');
    Route::post('settings/electronic-invoicing', [EasyFactuSettingsController::class, 'update'])->name('settings.electronic-invoicing.update');
    Route::post('settings/electronic-invoicing/test', [EasyFactuSettingsController::class, 'testConnection'])->name('settings.electronic-invoicing.test');
});
