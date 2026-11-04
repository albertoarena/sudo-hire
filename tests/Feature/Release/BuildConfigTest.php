<?php

use Illuminate\Support\Facades\File;
use Native\Desktop\Builder\Builder;
use Native\Desktop\Drivers\Electron\Facades\Updater;

it('names the author and the copyright of the app', function () {
    expect(config('nativephp.author'))->toBe('Alberto Arena')
        ->and(config('nativephp.copyright'))->toBe('Copyright (c) 2026 Alberto Arena. MIT license.');
});

it('reads the version from NATIVEPHP_APP_VERSION', function () {
    $_ENV['NATIVEPHP_APP_VERSION'] = '1.2.3';
    $_SERVER['NATIVEPHP_APP_VERSION'] = '1.2.3';

    try {
        $config = require config_path('nativephp.php');
    } finally {
        unset($_ENV['NATIVEPHP_APP_VERSION'], $_SERVER['NATIVEPHP_APP_VERSION']);
    }

    expect($config['version'])->toBe('1.2.3');
});

it('builds the frontend before the build', function () {
    expect(config('nativephp.prebuild'))->toContain('npm run build');
});

it('turns the updater on with the public GitHub repository and v tags', function () {
    expect(config('nativephp.updater.enabled'))->toBeTrue()
        ->and(config('nativephp.updater.default'))->toBe('github');

    expect(Updater::builderOptions())->toBe([
        'provider' => 'github',
        'repo' => 'sudo-hire',
        'owner' => 'albertoarena',
        'vPrefixedTagName' => true,
        'private' => false,
        'channel' => 'latest',
        'releaseType' => 'draft',
    ]);
});

it('removes every signing, notarization and GitHub secret from the bundled .env', function () {
    $env = storage_path('framework/testing/release-env-'.uniqid());
    File::ensureDirectoryExists(dirname($env));
    File::put($env, implode("\n", [
        'APP_NAME="Sudo Hire"',
        'APP_KEY=base64:example',
        'CSC_LINK=example',
        'CSC_KEY_PASSWORD=example',
        'NATIVEPHP_APPLE_ID=example',
        'NATIVEPHP_APPLE_ID_PASS=example',
        'NATIVEPHP_APPLE_TEAM_ID=example',
        'GITHUB_TOKEN=example',
        'GITHUB_AUTOUPDATE_TOKEN=example',
        'GH_TOKEN=example',
        'AWS_SECRET_ACCESS_KEY=example',
        'MAIL_PASSWORD=example',
        'REDIS_PASSWORD=example',
    ]));

    try {
        app(Builder::class)->cleanEnvFile($env);
        $kept = File::get($env);
    } finally {
        File::delete($env);
    }

    expect($kept)->toContain('APP_NAME="Sudo Hire"')
        ->toContain('APP_KEY=base64:example')
        ->not->toContain('CSC_')
        ->not->toContain('NATIVEPHP_APPLE')
        ->not->toContain('GITHUB_')
        ->not->toContain('GH_TOKEN')
        ->not->toContain('AWS_')
        ->not->toContain('PASSWORD');
});
