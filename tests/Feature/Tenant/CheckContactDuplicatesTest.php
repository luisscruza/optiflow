<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['current_workspace_id' => $workspace->id]);
});

test('returns empty object when no duplicates exist', function (): void {
    $response = $this->actingAs($this->user)
        ->getJson('/api/contacts/check-duplicates?email=unique@example.com&phone=000-000-0000');

    $response->assertOk()->assertExactJson([]);
});

test('detects duplicate by email', function (): void {
    $contact = Contact::factory()->create(['email' => 'match@example.com']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/contacts/check-duplicates?email=match@example.com');

    $response->assertOk()->assertJson([
        'email' => ['id' => $contact->id, 'name' => $contact->name],
    ]);
});

test('detects duplicate by phone', function (): void {
    $contact = Contact::factory()->create(['phone_primary' => '809-555-1234']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/contacts/check-duplicates?phone=809-555-1234');

    $response->assertOk()->assertJson([
        'phone' => ['id' => $contact->id, 'name' => $contact->name],
    ]);
});

test('excludes current contact when exclude_id is provided', function (): void {
    $contact = Contact::factory()->create(['email' => 'self@example.com', 'phone_primary' => '809-555-0001']);

    $response = $this->actingAs($this->user)
        ->getJson("/api/contacts/check-duplicates?email=self@example.com&phone=809-555-0001&exclude_id={$contact->id}");

    $response->assertOk()->assertExactJson([]);
});

test('returns no match when email and phone are blank', function (): void {
    Contact::factory()->create(['email' => 'exists@example.com']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/contacts/check-duplicates');

    $response->assertOk()->assertExactJson([]);
});
