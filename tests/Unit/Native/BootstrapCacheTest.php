<?php

use App\Native\BootstrapCache;
use Illuminate\Foundation\Application;

function clearBootstrapCacheVariables(): void
{
    foreach (array_keys(BootstrapCache::FILES) as $name) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }
}

beforeEach(function () {
    clearBootstrapCacheVariables();
    $this->userData = sys_get_temp_dir().'/sudo-hire-bootstrap-cache-'.bin2hex(random_bytes(4));
});

afterEach(function () {
    clearBootstrapCacheVariables();
    @rmdir($this->userData.'/bootstrap/cache');
    @rmdir($this->userData.'/bootstrap');
    @rmdir($this->userData);
});

it('points the config, routes and events cache to Application Support when the app runs from its bundle', function () {
    BootstrapCache::keepOutsideTheBundle('/Applications/Sudo Hire.app/Contents/Resources/build/app', $this->userData);

    $folder = $this->userData.'/bootstrap/cache';

    expect(getenv('APP_CONFIG_CACHE'))->toBe("{$folder}/config.php")
        ->and(getenv('APP_ROUTES_CACHE'))->toBe("{$folder}/routes-v7.php")
        ->and(getenv('APP_EVENTS_CACHE'))->toBe("{$folder}/events.php")
        ->and($_ENV['APP_CONFIG_CACHE'])->toBe("{$folder}/config.php")
        ->and($_SERVER['APP_CONFIG_CACHE'])->toBe("{$folder}/config.php")
        ->and(is_dir($folder))->toBeTrue();
});

it('makes Laravel write its cache outside the bundle', function () {
    $basePath = '/Applications/Sudo Hire.app/Contents/Resources/build/app';
    BootstrapCache::keepOutsideTheBundle($basePath, $this->userData);

    $app = new Application($basePath);

    expect($app->getCachedConfigPath())->toBe($this->userData.'/bootstrap/cache/config.php')
        ->and($app->getCachedRoutesPath())->toBe($this->userData.'/bootstrap/cache/routes-v7.php')
        ->and($app->getCachedEventsPath())->toBe($this->userData.'/bootstrap/cache/events.php')
        ->and($app->getCachedPackagesPath())->toBe($basePath.'/bootstrap/cache/packages.php')
        ->and($app->getCachedServicesPath())->toBe($basePath.'/bootstrap/cache/services.php');
});

it('keeps the paths a secure build sets', function () {
    putenv('APP_CONFIG_CACHE=/secure/config.php');

    BootstrapCache::keepOutsideTheBundle('/Applications/Sudo Hire.app/Contents/Resources/build/app', $this->userData);

    expect(getenv('APP_CONFIG_CACHE'))->toBe('/secure/config.php')
        ->and(getenv('APP_ROUTES_CACHE'))->toBe($this->userData.'/bootstrap/cache/routes-v7.php');
});

it('changes nothing outside an app bundle or with no Application Support folder', function (string $basePath, string|false $userData) {
    BootstrapCache::keepOutsideTheBundle($basePath, $userData);

    foreach (array_keys(BootstrapCache::FILES) as $name) {
        expect(getenv($name))->toBeFalse()
            ->and($_ENV)->not->toHaveKey($name)
            ->and($_SERVER)->not->toHaveKey($name);
    }
})->with([
    'local development' => ['/Users/dev/sudo-hire', '/tmp/sudo-hire-dev'],
    'no user data path' => ['/Applications/Sudo Hire.app/Contents/Resources/build/app', false],
    'an empty user data path' => ['/Applications/Sudo Hire.app/Contents/Resources/build/app', ''],
]);
