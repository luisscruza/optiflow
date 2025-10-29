<?php

declare(strict_types=1);

use App\Http\Controllers\Api\NcfValidationController;
use App\Http\Controllers\Api\RNCRetrieveController;
use App\Http\Controllers\Api\UserSearchController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::post('/rnc', RNCRetrieveController::class);
Route::get('/users/search', [UserSearchController::class, 'search']);

Route::middleware([
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function (): void {

    Route::post('/invoices/validate-ncf', NcfValidationController::class);
});
