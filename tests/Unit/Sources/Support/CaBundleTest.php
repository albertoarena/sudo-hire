<?php

use App\Sources\Support\CaBundle;
use Symfony\Component\Process\Process;

afterEach(function () {
    putenv('SSL_CERT_FILE');
    unset($_ENV['SSL_CERT_FILE'], $_SERVER['SSL_CERT_FILE']);
});

it('passes the bundle of this process to child processes as SSL_CERT_FILE', function () {
    CaBundle::shareWithChildProcesses('/app/build/cacert.pem');

    expect(getenv('SSL_CERT_FILE'))->toBe('/app/build/cacert.pem')
        ->and($_ENV['SSL_CERT_FILE'])->toBe('/app/build/cacert.pem')
        ->and($_SERVER['SSL_CERT_FILE'])->toBe('/app/build/cacert.pem');
});

it('reaches a child process started with the default environment, as the scheduler starts hire:scan', function () {
    CaBundle::shareWithChildProcesses('/app/build/cacert.pem');

    $process = Process::fromShellCommandline('echo "$SSL_CERT_FILE"');
    $process->run();

    expect(trim($process->getOutput()))->toBe('/app/build/cacert.pem');
});

it('changes nothing when this process has no bundle', function (string|false $cainfo) {
    CaBundle::shareWithChildProcesses($cainfo);

    expect(getenv('SSL_CERT_FILE'))->toBeFalse()
        ->and($_ENV)->not->toHaveKey('SSL_CERT_FILE')
        ->and($_SERVER)->not->toHaveKey('SSL_CERT_FILE');
})->with([
    'no curl.cainfo' => [false],
    'an empty curl.cainfo' => [''],
]);
