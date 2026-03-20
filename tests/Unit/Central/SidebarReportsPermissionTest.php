<?php

declare(strict_types=1);

test('reports sidebar item is scoped to the view reports permission', function (): void {
    $sidebar = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    expect($sidebar)
        ->toBeString()
        ->toContain("...(can('view reports')")
        ->toContain("title: 'Reportes'")
        ->toContain("href: '/reports'");
});
