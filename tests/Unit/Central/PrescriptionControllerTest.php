<?php

declare(strict_types=1);

test('prescription controller resolves the company logo through tenant assets', function (): void {
    $controller = file_get_contents(app_path('Http/Controllers/PrescriptionController.php'));

    expect($controller)
        ->toBeString()
        ->toContain('\'company\' => $this->getCompanyDetails()')
        ->toContain('tenant_asset(')
        ->toContain('$companyDetails[\'logo\']');
});

test('prescription show page uses the resolved company logo url directly', function (): void {
    $page = file_get_contents(resource_path('js/pages/prescriptions/show.tsx'));

    expect($page)
        ->toBeString()
        ->toContain('src={company.logo}')
        ->not->toContain('src={`/storage/${company.logo}`}');
});
