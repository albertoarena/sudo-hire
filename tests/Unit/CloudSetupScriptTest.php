<?php

/**
 * Runs scripts/cloud-setup.sh against temporary home folders.
 *
 * @param  array<string, string>  $env
 * @return array{exit: int, output: string}
 */
function runCloudSetup(array $homes, array $env = []): array
{
    $script = dirname(__DIR__, 2).'/scripts/cloud-setup.sh';

    $process = proc_open(
        ['bash', $script],
        [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
        $pipes,
        null,
        array_merge(['PATH' => (string) getenv('PATH'), 'CLOUD_SETUP_HOMES' => implode(' ', $homes)], $env),
    );

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return ['exit' => proc_close($process), 'output' => $output];
}

function tempHome(): string
{
    $dir = sys_get_temp_dir().'/cloud-setup-'.bin2hex(random_bytes(6));
    mkdir($dir);

    return $dir;
}

function gitConfig(string $home, string $key): string
{
    return trim((string) shell_exec('git config --file '.escapeshellarg("{$home}/.gitconfig").' '.escapeshellarg($key)));
}

it('turns off AI attribution and commit signing in every home folder', function () {
    $homes = [tempHome(), tempHome()];

    $result = runCloudSetup($homes);

    expect($result['exit'])->toBe(0);

    foreach ($homes as $home) {
        $settings = json_decode((string) file_get_contents("{$home}/.claude/settings.json"), true);

        expect($settings['attribution'])->toBe(['commit' => '', 'pr' => '', 'commitTrailers' => false, 'sessionUrl' => false])
            ->and(gitConfig($home, 'commit.gpgsign'))->toBe('false');
    }
});

it('exits with 0 when the git identity variables are empty', function () {
    $home = tempHome();

    $result = runCloudSetup([$home], ['GIT_AUTHOR_NAME' => '', 'GIT_AUTHOR_EMAIL' => '']);

    expect($result['exit'])->toBe(0)
        ->and(gitConfig($home, 'user.email'))->toBe('');
});

it('sets the git identity from the variables', function () {
    $home = tempHome();

    runCloudSetup([$home], ['GIT_AUTHOR_NAME' => 'Ada Example', 'GIT_AUTHOR_EMAIL' => 'ada@example.com']);

    expect(gitConfig($home, 'user.name'))->toBe('Ada Example')
        ->and(gitConfig($home, 'user.email'))->toBe('ada@example.com');
});

it('keeps other settings when the settings file exists', function () {
    $home = tempHome();
    mkdir("{$home}/.claude");
    file_put_contents("{$home}/.claude/settings.json", '{"model":"opus","attribution":{"commit":"old value"}}');

    $result = runCloudSetup([$home]);
    $settings = json_decode((string) file_get_contents("{$home}/.claude/settings.json"), true);

    expect($result['exit'])->toBe(0)
        ->and($settings['model'])->toBe('opus')
        ->and($settings['attribution']['commit'])->toBe('');
})->skip(fn () => trim((string) shell_exec('command -v jq')) === '', 'jq is not installed');

it('skips a home folder that does not exist', function () {
    $missing = sys_get_temp_dir().'/cloud-setup-missing-'.bin2hex(random_bytes(6));

    $result = runCloudSetup([$missing]);

    expect($result['exit'])->toBe(0)
        ->and(file_exists($missing))->toBeFalse();
});
