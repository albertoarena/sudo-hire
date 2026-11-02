<?php

namespace App\Http\Controllers\Api;

use App\List\SourceStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * GET /api/diagnostics: the report for "Copy diagnostic report". It has the app, macOS and
 * PHP versions and the status of each source. Never settings, keywords, companies or jobs.
 * Source errors give the kind only: a message can hold a URL.
 */
final class DiagnosticsController
{
    public function __invoke(SourceStatuses $statuses): JsonResponse
    {
        return response()->json([
            'appVersion' => (string) config('nativephp.version'),
            'macosVersion' => $this->macosVersion(),
            'phpVersion' => PHP_VERSION,
            'sources' => array_map(fn (array $status) => [
                'key' => $status['key'],
                'enabled' => $status['enabled'],
                'lastRunAt' => $status['lastRunAt'],
                'lastSuccessAt' => $status['lastSuccessAt'],
                'errorKind' => $status['lastError']['kind'] ?? null,
            ], $statuses->all()),
        ]);
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
