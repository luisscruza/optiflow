<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyDetailsRequest;
use App\Models\CompanyDetail;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyDetailsController extends Controller
{
    /**
     * Show the form for editing company details.
     */
    public function edit(): Response
    {
        $companyDetails = CompanyDetail::getAll();

        // Convert logo path to full URL if it exists
        if (! empty($companyDetails['logo'])) {
            $companyDetails['logo'] = asset('storage/'.$companyDetails['logo']);
        }

        return Inertia::render('configuration/company-details/edit', [
            'companyDetails' => $companyDetails,
        ]);
    }

    /**
     * Update the company details.
     */
    public function update(UpdateCompanyDetailsRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        // Handle file upload for logo
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $logoPath = $request->file('logo')->store('company-logos', 'public');
            $validatedData['logo'] = $logoPath;
        } else {
            // Remove logo from validated data if no file was uploaded
            unset($validatedData['logo']);
        }

        foreach ($validatedData as $key => $value) {
            CompanyDetail::setByKey($key, $value ?? '');
        }

        return redirect()
            ->route('company-details.edit')
            ->with('success', 'Company details updated successfully.');
    }
}
