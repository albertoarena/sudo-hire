<?php

use App\Scan\MacNotifier;
use App\Scan\Notifier;
use Symfony\Component\Process\Process;

it('is a notifier', function () {
    expect(new MacNotifier)->toBeInstanceOf(Notifier::class);
});

it('runs osascript with display notification and the text as an argument', function () {
    expect((new MacNotifier)->command('6 new jobs: 4 Laravel, 2 PHP'))->toBe([
        'osascript',
        '-e', 'on run argv',
        '-e', 'display notification (item 1 of argv) with title "Sudo Hire"',
        '-e', 'end run',
        '6 new jobs: 4 Laravel, 2 PHP',
    ]);
});

it('keeps a text with quotes and backslashes as one argument, out of the script', function () {
    $text = 'Senior "PHP" Dev \ C:\path\" end';
    $command = (new MacNotifier)->command($text);
    $script = implode("\n", array_slice($command, 1, -1));

    expect(end($command))->toBe($text)
        ->and($script)->not->toContain('PHP')
        ->and(count($command))->toBe(8);

    // The process gets an argument list: the shell line quotes the text as one word.
    $line = (new Process($command))->getCommandLine();
    expect($line)->toEndWith(' '.escapeshellarg($text));
});
