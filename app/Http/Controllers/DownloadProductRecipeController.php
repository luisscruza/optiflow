<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\ProductRecipe;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Context;

final class DownloadProductRecipeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ProductRecipe $productRecipe, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PrescriptionsView), 403);

        $workspace = Context::get('workspace');

        abort_if($workspace === null, 404);

        abort_unless($productRecipe->workspace_id === $workspace->id, 404);

        if (! defined('DOMPDF_ENABLE_REMOTE')) {
            define('DOMPDF_ENABLE_REMOTE', false);
        }

        $productRecipe->load([
            'contact',
            'optometrist',
            'product',
            'workspace',
        ]);

        $pdf = Pdf::loadView('product-recipes.pdf', [
            'productRecipe' => $productRecipe,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $contactName = str($productRecipe->contact->name)->slug('-')->value();
        $filename = "recetario-productos-{$contactName}-{$productRecipe->id}.pdf";

        return $pdf->stream($filename);
    }
}
