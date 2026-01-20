<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateCompanyDetailsAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\UpdateCompanyDetailsRequest;
use App\Models\CompanyDetail;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyDetailsController
{
    /**
     * Show the form for editing company details.
     */
    public function edit(): Response
    {
        $companyDetails = CompanyDetail::getAll();

        if (! empty($companyDetails['logo'])) {
            // Use tenant_asset helper which points to TenantAssetsController
            $companyDetails['logo'] = tenant_asset($companyDetails['logo']);
        }

        return Inertia::render('configuration/company-details/edit', [
            'companyDetails' => $companyDetails,
        ]);
    }

    /**
     * Update the company details.
     */
    public function update(UpdateCompanyDetailsRequest $request, UpdateCompanyDetailsAction $action): RedirectResponse
    {
        try {
            $action->handle($request->validated(), $request->file('logo'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('company-details.edit')
            ->with('success', 'Company details updated successfully.');
    }
}
