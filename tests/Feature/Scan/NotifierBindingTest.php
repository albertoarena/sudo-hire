<?php

use App\Scan\MacNotifier;
use App\Scan\NativeNotifier;
use App\Scan\Notifier;

it('uses native notifications inside the app', function () {
    config(['nativephp-internal.running' => true]);

    expect(app(Notifier::class))->toBeInstanceOf(NativeNotifier::class);
});

it('uses osascript outside the app', function () {
    config(['nativephp-internal.running' => false]);

    expect(app(Notifier::class))->toBeInstanceOf(MacNotifier::class);
});
