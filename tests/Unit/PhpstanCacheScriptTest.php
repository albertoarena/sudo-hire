<?php

/**
 * Runs scripts/phpstan-cache.sh against a local git repository, so no network is used.
 *
 * @param  array<string, string>  $env
 * @return array{exit: int, output: string}
 */
function runPhpstanCache(array $env): array
{
    $script = dirname(__DIR__, 2).'/scripts/phpstan-cache.sh';

    $process = proc_open(
        ['bash', $script],
        [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
        $pipes,
        dirname(__DIR__, 2),
        array_merge(['PATH' => (string) getenv('PATH'), 'HOME' => (string) getenv('HOME')], $env),
    );

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return ['exit' => proc_close($process), 'output' => $output];
}

/**
 * @return array{version: string, url: string}
 */
function lockedPhpstan(): array
{
    $lock = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.lock'), true);

    foreach ($lock['packages-dev'] as $package) {
        if ($package['name'] === 'phpstan/phpstan') {
            return ['version' => $package['version'], 'url' => $package['dist']['url']];
        }
    }

    throw new RuntimeException('phpstan/phpstan is not in composer.lock');
}

/**
 * A git repository with one commit, tagged with the locked PHPStan version.
 */
function fakePhpstanRepository(string $version): string
{
    $dir = sys_get_temp_dir().'/phpstan-repo-'.bin2hex(random_bytes(6));
    mkdir($dir);
    file_put_contents("{$dir}/phpstan.phar", 'fake phar');
    file_put_contents("{$dir}/composer.json", '{"name":"phpstan/phpstan"}');

    $git = 'git -C '.escapeshellarg($dir);
    shell_exec("{$git} init -q && {$git} add . && {$git} -c user.name=Test -c user.email=test@example.com commit -q -m init && {$git} tag {$version}");

    return $dir;
}

it('puts a zip of the locked tag where Composer looks for it', function () {
    $phpstan = lockedPhpstan();
    $cacheDir = sys_get_temp_dir().'/composer-cache-'.bin2hex(random_bytes(6));

    $result = runPhpstanCache([
        'PHPSTAN_GIT_URL' => 'file://'.fakePhpstanRepository($phpstan['version']),
        'COMPOSER_CACHE_DIR' => $cacheDir,
    ]);

    $zipPath = "{$cacheDir}/files/phpstan/phpstan/".sha1($phpstan['url']).'.zip';

    expect($result['exit'])->toBe(0, $result['output'])
        ->and(file_exists($zipPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = array_map(fn (int $i) => (string) $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
    $zip->close();

    expect(collect($names)->contains(fn (string $name) => str_ends_with($name, '/phpstan.phar')))->toBeTrue()
        ->and(collect($names)->contains(fn (string $name) => str_contains($name, '/.git/')))->toBeFalse();
});

it('does nothing when the zip is already in the cache', function () {
    $phpstan = lockedPhpstan();
    $cacheDir = sys_get_temp_dir().'/composer-cache-'.bin2hex(random_bytes(6));
    $zipPath = "{$cacheDir}/files/phpstan/phpstan/".sha1($phpstan['url']).'.zip';
    mkdir(dirname($zipPath), 0777, true);
    file_put_contents($zipPath, 'already here');

    $result = runPhpstanCache([
        'PHPSTAN_GIT_URL' => 'file:///does/not/exist',
        'COMPOSER_CACHE_DIR' => $cacheDir,
    ]);

    expect($result['exit'])->toBe(0, $result['output'])
        ->and(file_get_contents($zipPath))->toBe('already here');
});

it('fails with a message when the tag cannot be cloned', function () {
    $result = runPhpstanCache([
        'PHPSTAN_GIT_URL' => 'file:///does/not/exist',
        'COMPOSER_CACHE_DIR' => sys_get_temp_dir().'/composer-cache-'.bin2hex(random_bytes(6)),
    ]);

    expect($result['exit'])->not->toBe(0)
        ->and($result['output'])->toContain('phpstan-cache:');
});
