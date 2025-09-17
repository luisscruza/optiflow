<?php

declare(strict_types=1);

use App\Models\CompanyDetail;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
});

it('displays company details edit page', function () {
    $response = $this->actingAs($this->user)->get('/company-details');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('configuration/company-details/edit')
            ->has('companyDetails')
        );
});

it('updates company details successfully', function () {
    $companyData = [
        'company_name' => 'Test Company',
        'address' => '123 Test Street',
        'phone' => '+1 234 567 8900',
        'email' => 'test@company.com',
        'tax_id' => '123456789',
        'currency' => 'USD',
    ];

    $response = $this->actingAs($this->user)
        ->patch('/company-details', $companyData);

    $response->assertRedirect('/company-details')
        ->assertSessionHas('success', 'Company details updated successfully.');

    // Verify data was saved
    expect(CompanyDetail::getByKey('company_name'))->toBe('Test Company');
    expect(CompanyDetail::getByKey('email'))->toBe('test@company.com');
    expect(CompanyDetail::getByKey('tax_id'))->toBe('123456789');
});

it('validates required company name', function () {
    $response = $this->actingAs($this->user)
        ->patch('/company-details', [
            'company_name' => '',
        ]);

    $response->assertSessionHasErrors(['company_name']);
});

it('validates email format', function () {
    $response = $this->actingAs($this->user)
        ->patch('/company-details', [
            'company_name' => 'Test Company',
            'email' => 'invalid-email',
        ]);

    $response->assertSessionHasErrors(['email']);
});

it('uploads company logo successfully', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($this->user)
        ->patch('/company-details', [
            'company_name' => 'Test Company',
            'logo' => $file,
        ]);

    $response->assertRedirect('/company-details');

    // Verify file was stored
    $logoPath = CompanyDetail::getByKey('logo');
    expect($logoPath)->toContain('company-logos/');
    Storage::disk('public')->assertExists($logoPath);
});

it('validates logo file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($this->user)
        ->patch('/company-details', [
            'company_name' => 'Test Company',
            'logo' => $file,
        ]);

    $response->assertSessionHasErrors(['logo']);
});

it('validates logo file size', function () {
    $file = UploadedFile::fake()->image('large-logo.png')->size(3000); // 3MB file

    $response = $this->actingAs($this->user)
        ->patch('/company-details', [
            'company_name' => 'Test Company',
            'logo' => $file,
        ]);

    $response->assertSessionHasErrors(['logo']);
});
