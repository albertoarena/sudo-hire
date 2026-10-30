<?php

use App\Settings\SystemTimezone;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/system-timezone-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->link = $this->dir.'/localtime';
});

afterEach(function () {
    @unlink($this->link);
    @rmdir($this->dir);
});

it('reads the timezone from the macOS link', function (string $target, string $timezone) {
    symlink($target, $this->link);

    expect((new SystemTimezone($this->link))->name())->toBe($timezone);
})->with([
    'macOS' => ['/var/db/timezone/zoneinfo/Europe/Rome', 'Europe/Rome'],
    'older macOS' => ['/usr/share/zoneinfo/America/Los_Angeles', 'America/Los_Angeles'],
    'three parts' => ['/var/db/timezone/zoneinfo/America/Argentina/Buenos_Aires', 'America/Argentina/Buenos_Aires'],
    'UTC' => ['/var/db/timezone/zoneinfo/UTC', 'UTC'],
]);

it('falls back to UTC', function (?string $target) {
    if ($target !== null) {
        symlink($target, $this->link);
    }

    expect((new SystemTimezone($this->link))->name())->toBe('UTC');
})->with([
    'no link' => [null],
    'not a zoneinfo path' => ['/tmp/somewhere'],
    'unknown zone' => ['/var/db/timezone/zoneinfo/Mars/Olympus'],
]);

it('falls back to UTC for a plain file', function () {
    file_put_contents($this->link, 'TZif');

    expect((new SystemTimezone($this->link))->name())->toBe('UTC');
});
