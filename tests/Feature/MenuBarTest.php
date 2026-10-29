<?php

use App\Providers\NativeAppServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Facades\Window;

it('starts as a menu bar app without opening a window', function () {
    Http::fake();
    Window::fake();

    (new NativeAppServiceProvider)->boot();

    Window::assertOpenedCount(0);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/create')
        && $request['showDockIcon'] === false
        && $request['tooltip'] === 'Sudo Hire');
});
