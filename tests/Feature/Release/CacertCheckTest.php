<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Writes a CA bundle with the header line of the Mozilla bundle that NativePHP ships.
 */
function fakeCacert(string $dataDate): string
{
    $path = storage_path('framework/testing/cacert-'.uniqid().'.pem');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "##\n## Bundle of CA Root Certificates\n##\n## Certificate data from Mozilla as of: {$dataDate}\n##\n");

    return $path;
}

afterEach(function () {
    CarbonImmutable::setTestNow();
    File::delete(File::glob(storage_path('framework/testing/cacert-*.pem')));
});

it('passes a CA bundle that is 10 days old and gives its date', function () {
    CarbonImmutable::setTestNow('2026-11-03 12:00:00');
    $path = fakeCacert('Sat Oct 24 13:32:40 2026 GMT');

    $this->artisan('release:check-cacert', ['--path' => $path])
        ->expectsOutputToContain('2026-10-24')
        ->assertExitCode(0);
});

it('fails a CA bundle that is 200 days old and gives its date', function () {
    CarbonImmutable::setTestNow('2026-11-03 12:00:00');
    $path = fakeCacert('Thu Apr 16 13:32:40 2026 GMT');

    $this->artisan('release:check-cacert', ['--path' => $path])
        ->expectsOutputToContain('2026-04-16')
        ->expectsOutputToContain('older than 180 days')
        ->assertExitCode(1);
});

it('fails a CA bundle with no date', function () {
    $path = storage_path('framework/testing/cacert-'.uniqid().'.pem');
    File::put($path, "-----BEGIN CERTIFICATE-----\n");

    $this->artisan('release:check-cacert', ['--path' => $path])
        ->expectsOutputToContain('no date')
        ->assertExitCode(1);
});

it('fails when the CA bundle is missing', function () {
    $this->artisan('release:check-cacert', ['--path' => storage_path('framework/testing/cacert-missing.pem')])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});

it('checks the CA bundle that NativePHP copies into the build by default', function () {
    $bundle = base_path('vendor/nativephp/desktop/resources/cacert.pem');
    preg_match('/Certificate data from Mozilla as of: (.+)/', File::get($bundle), $match);
    CarbonImmutable::setTestNow(CarbonImmutable::parse($match[1])->addDay());

    $this->artisan('release:check-cacert')
        ->expectsOutputToContain(CarbonImmutable::parse($match[1])->toDateString())
        ->assertExitCode(0);
});
