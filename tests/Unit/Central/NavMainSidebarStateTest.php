<?php

declare(strict_types=1);

test('sidebar groups open only when one of their children is active', function (): void {
    $navMain = file_get_contents(resource_path('js/components/nav-main.tsx'));

    expect($navMain)
        ->toBeString()
        ->toContain('const isItemActive = (item: NavItem): boolean => {')
        ->toContain("return href !== '' && page.url.startsWith(href);")
        ->toContain('defaultOpen={item.items.some(isItemActive)}')
        ->toContain('isActive={isItemActive(subItem)}')
        ->toContain('isActive={isItemActive(item)}')
        ->not->toContain('<Collapsible key={item.title} asChild defaultOpen>');
});
