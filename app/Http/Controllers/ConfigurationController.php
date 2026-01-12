<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ConfigurationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can(Permission::ConfigurationView), 403);

        return Inertia::render('configuration/index');
    }
}
