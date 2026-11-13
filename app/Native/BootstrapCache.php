<?php

namespace App\Native;

/**
 * NativePHP runs `artisan optimize` each time the app starts. In a build with no secure bundle it does not
 * set the cache paths, so Laravel writes config.php, routes-v7.php and events.php into bootstrap/cache
 * inside the signed .app, and the signature no longer matches. This points those three files to
 * bootstrap/cache in Application Support, the folder NativePHP makes for them.
 * packages.php and services.php stay in the bundle: the build writes them and they are part of the signature.
 * See docs/DECISIONS.md, "The bootstrap cache stays outside the signed app".
 */
final class BootstrapCache
{
    /** @var array<string, string> */
    public const FILES = [
        'APP_CONFIG_CACHE' => 'config.php',
        'APP_ROUTES_CACHE' => 'routes-v7.php',
        'APP_EVENTS_CACHE' => 'events.php',
    ];

    public static function keepOutsideTheBundle(string $basePath, string|false $userDataPath): void
    {
        if (! str_contains($basePath, '.app/Contents/Resources/') || $userDataPath === false || $userDataPath === '') {
            return;
        }

        $folder = rtrim($userDataPath, '/').'/bootstrap/cache';

        if (! is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        foreach (self::FILES as $name => $file) {
            // A secure build already sets them.
            if (getenv($name) !== false || isset($_ENV[$name]) || isset($_SERVER[$name])) {
                continue;
            }

            $path = "{$folder}/{$file}";

            // Laravel reads them through Env, and a process that PHP starts gets them too.
            putenv("{$name}={$path}");
            $_ENV[$name] = $path;
            $_SERVER[$name] = $path;
        }
    }
}
