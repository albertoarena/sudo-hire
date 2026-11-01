<?php

namespace App\Scan;

use Symfony\Component\Process\Process;

/**
 * Shows a macOS notification with osascript. The text is an argument of the
 * script (on run argv), never part of the script, so quotes in a job title
 * cannot break it. The process gets an argument list, not a shell string.
 */
final class MacNotifier implements Notifier
{
    public const string TITLE = 'Sudo Hire';

    public const int TIMEOUT_SECONDS = 10;

    public function send(string $text): void
    {
        $process = new Process($this->command($text), timeout: self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (\Throwable $error) {
            throw new NotificationFailed("osascript failed: {$error->getMessage()}", 0, $error);
        }

        if (! $process->isSuccessful()) {
            $output = trim($process->getErrorOutput()) ?: "exit code {$process->getExitCode()}";

            throw new NotificationFailed("osascript failed: {$output}");
        }
    }

    /**
     * @return list<string>
     */
    public function command(string $text): array
    {
        return [
            'osascript',
            '-e', 'on run argv',
            '-e', 'display notification (item 1 of argv) with title "'.self::TITLE.'"',
            '-e', 'end run',
            $text,
        ];
    }
}
