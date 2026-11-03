<?php

use App\Models\StoredSettings;
use App\Providers\NativeAppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Facades\Window;
use Native\Desktop\Windows\Window as NativeWindow;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

it('starts as a menu bar app without opening a window', function () {
    ApiHelpers::settings(['country' => 'IT']);
    Http::fake();
    Window::fake();

    (new NativeAppServiceProvider)->boot();

    Window::assertOpenedCount(0);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/create')
        && $request['showDockIcon'] === false
        && $request['tooltip'] === 'Sudo Hire');
});

it('opens the first run on start when no country is stored', function () {
    StoredSettings::query()->delete();
    Http::fake();
    $windows = Window::fake()->alwaysReturnWindows([new NativeWindow('first-run')]);

    (new NativeAppServiceProvider)->boot();

    $windows->assertOpened('first-run');
});

it('opens the first run with its title, size and page, without the developer tools', function () {
    ApiHelpers::settings(['country' => null]);
    config(['app.debug' => true]);
    Http::fake();

    (new NativeAppServiceProvider)->boot();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/open')
        && $request['id'] === 'first-run'
        && str_ends_with($request['url'], '/first-run')
        && $request['title'] === 'Welcome to Sudo Hire'
        && $request['width'] === 520
        && $request['resizable'] === false
        && $request['showDevTools'] === false);
});

it('shows the stamp mark as a template image in the menu bar', function () {
    Http::fake();

    (new NativeAppServiceProvider)->boot();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/create')
        && $request['icon'] === public_path('menuBarIconTemplate.png'));
});

it('has the menu bar mark at 15 points, with a 2x image', function () {
    // macOS tints an image whose name ends in "Template", so the mark follows light and dark menu bars.
    $one = getimagesize(public_path('menuBarIconTemplate.png'));
    $two = getimagesize(public_path('menuBarIconTemplate@2x.png'));

    expect([$one[0] ?? null, $one[1] ?? null])->toBe([15, 15])
        ->and([$two[0] ?? null, $two[1] ?? null])->toBe([30, 30])
        ->and($one['mime'] ?? null)->toBe('image/png');
});
