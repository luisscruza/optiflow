<?php

declare(strict_types=1);

use Database\Factories\WhatsappAccountFactory;

test('to array', function (): void {
    $account = WhatsappAccountFactory::new()->create()->refresh();

    expect(array_keys($account->toArray()))->toBe([
        'id',
        'name',
        'phone_number_id',
        'business_account_id',
        'display_phone_number',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
