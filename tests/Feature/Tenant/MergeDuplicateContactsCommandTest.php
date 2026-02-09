<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;

test('merges duplicate contacts and reassigns references', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $survivor = Contact::factory()->forWorkspace($workspace)->create([
        'name' => 'Juan Perez',
        'email' => 'juan.perez@example.com',
        'phone_primary' => null,
    ]);

    $duplicate = Contact::factory()->forWorkspace($workspace)->create([
        'name' => 'Juan Perez',
        'email' => 'JUAN.PEREZ@EXAMPLE.COM',
        'phone_primary' => '809-555-1212',
    ]);

    $invoice = Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'contact_id' => $duplicate->id,
    ]);

    $prescription = Prescription::factory()->create([
        'workspace_id' => $workspace->id,
        'patient_id' => $duplicate->id,
        'created_by' => $user->id,
    ]);

    Artisan::call('contacts:merge-duplicates', [
        '--execute' => true,
    ]);

    expect(Contact::query()->where('id', $duplicate->id)->exists())->toBeFalse();

    $survivor->refresh();
    $invoice->refresh();
    $prescription->refresh();

    expect($invoice->contact_id)->toBe($survivor->id);
    expect($prescription->patient_id)->toBe($survivor->id);
    expect($survivor->phone_primary)->toBe('809-555-1212');
});
