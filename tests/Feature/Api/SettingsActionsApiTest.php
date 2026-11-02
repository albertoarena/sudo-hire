<?php

use App\Models\AtsCompany;
use App\Models\SourceRun;
use App\Sources\Ats\Ats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(['*/shell/open-item' => Http::response(['result' => '']), '*' => Http::response()]);
    Process::fake(['sw_vers -productVersion' => Process::result("15.1\n")]);
    ApiHelpers::settings();
});

it('shows the database file in Finder', function (array $body) {
    config(['database.connections.sqlite.database' => '/var/data/sudo-hire/nativephp.sqlite']);

    $this->postJson('/api/app/show-data-folder', $body)->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'shell/show-item-in-folder')
        && $request['path'] === '/var/data/sudo-hire/nativephp.sqlite');
})->with([
    'no body' => [[]],
    'a path in the request' => [['path' => '/etc/hosts']],
]);

it('opens the log folder', function (array $body) {
    $this->postJson('/api/app/open-log-folder', $body)->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'shell/open-item')
        && $request['path'] === storage_path('logs'));
})->with([
    'no body' => [[]],
    'a path in the request' => [['path' => '/etc']],
]);

it('copies the diagnostic report to the clipboard', function () {
    config(['nativephp.version' => '1.2.3']);
    ApiHelpers::settings(['disabledSources' => ['jobicy']]);
    SourceRun::query()->create(['source' => 'larajobs', 'started_at' => '2026-11-02T08:00:00Z', 'finished_at' => '2026-11-02T08:00:02Z', 'error' => 'cURL error 60 for https://larajobs.com/feed', 'error_kind' => 'certificate']);

    $this->postJson('/api/app/copy-diagnostics', ['text' => 'something else'])->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), 'clipboard/text')) {
            return false;
        }

        $text = $request['text'];

        return str_starts_with($text, "Sudo Hire 1.2.3\nmacOS 15.1\nPHP ".PHP_VERSION."\n\nSources\n")
            && str_contains($text, "larajobs: on, last run 2026-11-02T08:00:00Z, last success never, error certificate\n")
            && str_contains($text, "jobicy: off, last run never, last success never, no error\n")
            && ! str_contains($text, 'something else');
    });
});

it('has no settings, keywords, companies or jobs in the copied report', function () {
    ApiHelpers::settings(['keywordsInclude' => ['secretkeyword'], 'companyBlocklist' => ['Blockedco'], 'timezone' => 'Asia/Tokyo']);
    AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'privateco', 'display_name' => 'Privateco']);
    ApiHelpers::job('1', ['title' => 'Unique Title For Diagnostics', 'company' => 'Jobcompany']);
    SourceRun::query()->create(['source' => 'larajobs', 'started_at' => '2026-11-02T08:00:00Z', 'error' => 'cURL error 60 for https://larajobs.com/feed', 'error_kind' => 'certificate']);

    $this->postJson('/api/app/copy-diagnostics')->assertNoContent();

    Http::assertSent(function (Request $request) {
        foreach (['secretkeyword', 'Blockedco', 'Asia', 'Tokyo', 'IT', 'privateco', 'Privateco', 'Unique Title', 'Jobcompany', 'example.com', 'larajobs.com', 'country', 'keywords'] as $private) {
            if (str_contains($request['text'], $private)) {
                return false;
            }
        }

        return true;
    });
});

it('sets the title of the settings window from the pane', function (string $pane, string $title) {
    $this->postJson('/api/app/settings-title', ['pane' => $pane])->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/title')
        && $request['id'] === 'settings'
        && $request['title'] === $title);
})->with([
    ['where', 'Where you work'],
    ['jobs', 'Jobs'],
    ['pay', 'Pay'],
    ['notifications', 'Notifications'],
    ['sources', 'Sources'],
    ['companies', 'Companies'],
    ['privacy', 'Privacy'],
    ['troubleshooting', 'Troubleshooting'],
]);

it('refuses a pane that is not in the list', function (mixed $pane) {
    $this->postJson('/api/app/settings-title', ['pane' => $pane])->assertUnprocessable();

    Http::assertNothingSent();
})->with(['a title' => ['Anything'], 'empty' => [''], 'missing' => [null]]);
