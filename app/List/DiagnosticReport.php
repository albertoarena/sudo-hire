<?php

namespace App\List;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * The diagnostic report: the app, macOS and PHP versions and the status of each source.
 * Never settings, keywords, companies or jobs. Source errors give the kind only: a message can hold a URL.
 */
final class DiagnosticReport
{
    public function __construct(private readonly SourceStatuses $statuses) {}

    /**
     * @return array{appVersion: string, macosVersion: string|null, phpVersion: string, sources: list<array{key: string, enabled: bool, lastRunAt: string|null, lastSuccessAt: string|null, errorKind: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'appVersion' => (string) config('nativephp.version'),
            'macosVersion' => $this->macosVersion(),
            'phpVersion' => PHP_VERSION,
            'sources' => array_map(fn (array $status) => [
                'key' => $status['key'],
                'enabled' => $status['enabled'],
                'lastRunAt' => $status['lastRunAt'],
                'lastSuccessAt' => $status['lastSuccessAt'],
                'errorKind' => $status['lastError']['kind'] ?? null,
            ], $this->statuses->all()),
        ];
    }

    /**
     * The report as plain text, to paste into an issue.
     */
    public function text(): string
    {
        $report = $this->toArray();
        $lines = [
            "Sudo Hire {$report['appVersion']}",
            'macOS '.($report['macosVersion'] ?? 'unknown'),
            "PHP {$report['phpVersion']}",
            '',
            'Sources',
        ];

        foreach ($report['sources'] as $source) {
            $lines[] = sprintf(
                '%s: %s, last run %s, last success %s, %s',
                $source['key'],
                $source['enabled'] ? 'on' : 'off',
                $source['lastRunAt'] ?? 'never',
                $source['lastSuccessAt'] ?? 'never',
                $source['errorKind'] === null ? 'no error' : "error {$source['errorKind']}",
            );
        }

        return implode("\n", $lines)."\n";
    }

    private function macosVersion(): ?string
    {
        try {
            $result = Process::timeout(5)->run('sw_vers -productVersion');
        } catch (Throwable) {
            return null;
        }

        $version = trim($result->output());

        return $result->successful() && $version !== '' ? $version : null;
    }
}
