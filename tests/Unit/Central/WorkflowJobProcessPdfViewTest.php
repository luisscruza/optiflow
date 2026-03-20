<?php

declare(strict_types=1);

test('workflow job process pdf places the phone box below frame properties', function (): void {
    $view = file_get_contents(resource_path('views/workflow-jobs/process-pdf.blade.php'));

    $frameBoxPosition = mb_strpos($view, '<div class="frame-box">');
    $phoneBoxPosition = mb_strpos($view, '<div class="phone-box">');

    expect($view)
        ->toBeString()
        ->toContain('<div class="frame-box">')
        ->toContain('<div class="phone-box">')
        ->toContain('border-radius: 6px;')
        ->toContain('border: 1px solid #000;')
        ->and($frameBoxPosition)->not->toBeFalse()
        ->and($phoneBoxPosition)->not->toBeFalse()
        ->and($phoneBoxPosition)->toBeGreaterThan($frameBoxPosition);

    expect(str_contains($view, '<!-- Phone -->'))->toBeFalse();
});
