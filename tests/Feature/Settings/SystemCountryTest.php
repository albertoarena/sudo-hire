<?php

use App\Settings\SystemCountry;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

it('asks macOS for the region', function () {
    Process::fake(['defaults read -g AppleLocale' => Process::result("en_IT@currency=eur\n")]);

    expect((new SystemCountry)->code())->toBe('IT');

    Process::assertRan(fn (PendingProcess $process) => $process->command === 'defaults read -g AppleLocale');
});

it('gives no country when macOS has no region', function () {
    Process::fake(['defaults read -g AppleLocale' => Process::result('', 'does not exist', 1)]);

    expect((new SystemCountry)->code())->toBeNull();
});
