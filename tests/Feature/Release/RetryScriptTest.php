<?php

use Symfony\Component\Process\Process;

function runRetryScript(string ...$args): Process
{
    $process = new Process(['bash', base_path('scripts/retry.sh'), ...$args], env: ['RETRY_PAUSE_SECONDS' => '0']);
    $process->run();

    return $process;
}

it('stops at the first success', function () {
    $count = tempnam(sys_get_temp_dir(), 'retry');

    try {
        $process = runRetryScript('5', 'bash', '-c', 'echo x >> "$0"', $count);

        expect($process->getExitCode())->toBe(0)
            ->and(count(file($count)))->toBe(1);
    } finally {
        unlink($count);
    }
});

it('tries the given number of times and returns the last exit code', function () {
    $count = tempnam(sys_get_temp_dir(), 'retry');

    try {
        $process = runRetryScript('5', 'bash', '-c', 'echo x >> "$0"; exit 68', $count);

        expect($process->getExitCode())->toBe(68)
            ->and(count(file($count)))->toBe(5)
            ->and($process->getErrorOutput())->toContain('Attempt 5 of 5 failed (exit code 68)');
    } finally {
        unlink($count);
    }
});

it('succeeds when a later attempt works', function () {
    $count = tempnam(sys_get_temp_dir(), 'retry');

    try {
        $process = runRetryScript('5', 'bash', '-c', 'echo x >> "$0"; [ "$(wc -l < "$0")" -ge 3 ]', $count);

        expect($process->getExitCode())->toBe(0)
            ->and(count(file($count)))->toBe(3);
    } finally {
        unlink($count);
    }
});

it('refuses a count that is not a positive number', function () {
    expect(runRetryScript('0', 'true')->getExitCode())->toBe(2)
        ->and(runRetryScript('x', 'true')->getExitCode())->toBe(2);
});
