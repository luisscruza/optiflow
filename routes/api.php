<?php

declare(strict_types=1);

use App\Http\Controllers\Api\RNCRetrieveController;
use App\Http\Controllers\Api\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::post('/rnc', RNCRetrieveController::class);
Route::get('/users/search', [UserSearchController::class, 'search']);
