<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The windows that NativePHP says are open.
    $this->openWindows = [];
    // What the internal API answers to a notification.
    $this->notificationResponse = fn () => Http::response(['reference' => 'sudo-hire-test']);
    Http::fake([
        '*window/all' => fn () => Http::response($this->openWindows),
        '*/notification' => fn () => ($this->notificationResponse)(),
        '*' => Http::response(),
    ]);
});

it('opens a link in the default browser', function (string $url) {
    $this->postJson('/api/app/open-url', ['url' => $url])->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'shell/open-external')
        && $request['url'] === $url);
})->with([
    'https' => ['https://himalayas.app/companies/acme/jobs/php-developer?utm_source=api'],
    'http' => ['http://larajobs.com/job/1'],
]);

it('refuses a link that is not a web address', function (mixed $url) {
    $this->postJson('/api/app/open-url', ['url' => $url])->assertStatus(422);

    Http::assertNothingSent();
})->with([
    'a file' => ['file:///etc/hosts'],
    'a script' => ['javascript:alert(1)'],
    'an app scheme' => ['x-apple.systempreferences:com.apple.preference'],
    'not a url' => ['larajobs'],
    'empty' => [''],
    'missing' => [null],
]);

it('opens the settings window', function () {
    $this->postJson('/api/app/settings')->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/open')
        && $request['id'] === 'settings'
        && str_ends_with($request['url'], '/settings')
        && $request['title'] === 'Sudo Hire Settings');
});

it('opens the settings window without the developer tools, also in debug mode', function () {
    config(['app.debug' => true]);

    $this->postJson('/api/app/settings')->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/open')
        && $request['showDevTools'] === false);
});

it('opens the settings window on a pane', function () {
    $this->postJson('/api/app/settings', ['pane' => 'sources'])->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/open')
        && $request['id'] === 'settings'
        && str_ends_with($request['url'], '/settings?pane=sources'));
    Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), 'window/url'));
});

it('moves an open settings window to the pane', function () {
    $this->openWindows = [['id' => 'settings'], ['id' => 'first-run']];

    $this->postJson('/api/app/settings', ['pane' => 'sources'])->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/url')
        && $request['id'] === 'settings'
        && str_ends_with($request['url'], '/settings?pane=sources'));
    // The open window comes to the front.
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/open') && $request['id'] === 'settings');
});

it('refuses a pane that does not exist', function () {
    $this->postJson('/api/app/settings', ['pane' => 'secrets'])->assertStatus(422);

    Http::assertNothingSent();
});

it('opens the menu bar list and closes the first run', function () {
    $this->postJson('/api/app/open-list')->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'window/close') && $request['id'] === 'first-run');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/show'));
});

it('sends a test notification', function () {
    $this->postJson('/api/app/test-notification')->assertNoContent();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/notification')
        && $request['title'] === 'Sudo Hire'
        && $request['body'] === 'Notifications work. Sudo Hire sends one summary when new jobs match.'
        && $request['reference'] === 'sudo-hire-test');
});

it('answers 502 when the test notification does not show', function (Closure $response) {
    $this->notificationResponse = $response;

    $this->postJson('/api/app/test-notification')->assertStatus(502);
})->with([
    'refused' => [fn () => Http::response(['error' => 'Unauthorized'], 403)],
    'no answer' => [fn () => Http::failedConnection('Connection refused')],
]);

it('quits the app', function () {
    $this->postJson('/api/app/quit')->assertNoContent();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'app/quit'));
});

it('serves the page that mounts the settings window', function () {
    $this->withSession(['_token' => 'page-token'])
        ->get('/settings')
        ->assertOk()
        ->assertSee('id="settings"', escape: false)
        ->assertSee('<meta name="csrf-token" content="page-token">', escape: false);
});
