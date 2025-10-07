<?php

declare(strict_types=1);

test('can visit admin page', function (): void {

    $page = visit('/admin');

    $page->assertSee('Optiflow');
});
