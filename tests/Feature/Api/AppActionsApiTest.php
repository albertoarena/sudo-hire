<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
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
