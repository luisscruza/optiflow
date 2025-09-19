<?php

declare(strict_types=1);

use App\Http\Controllers\Api\RNCRetrieveController;
use Illuminate\Support\Facades\Route;

Route::post('/rnc', RNCRetrieveController::class);
