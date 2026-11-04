<?php

it('has the app icon source, drawn on the macOS icon grid', function () {
    $svg = file_get_contents(resource_path('icons/app-icon.svg'));

    expect($svg)->toContain('viewBox="0 0 1024 1024"')
        ->toContain('<rect x="100" y="100" width="824" height="824" rx="184"/>')
        ->toContain('#5E1B2B')
        ->toContain('#F4E8EA');
});

it('renders the app icon where NativePHP reads it', function () {
    $png = getimagesize(public_path('icon.png'));

    expect($png[0])->toBe(1024)
        ->and($png[1])->toBe(1024)
        ->and(file_get_contents(public_path('icon.icns'), length: 4))->toBe('icns');
});

it('has a script that renders the app icon again', function () {
    expect(is_executable(base_path('scripts/render-app-icon.sh')))->toBeTrue();
});
