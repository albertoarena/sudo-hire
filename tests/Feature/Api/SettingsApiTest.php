<?php

use App\Settings\SettingsStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    ApiHelpers::settings(['keywordsInclude' => ['laravel']]);
    Http::fake(['*/menu-bar/label' => Http::response()]);
});

it('gives the settings as toArray() gives them', function () {
    $this->getJson('/api/settings')
        ->assertOk()
        ->assertExactJson(app(SettingsStore::class)->load()->toArray())
        ->assertJsonPath('country', 'IT')
        ->assertJsonPath('keywordsInclude', ['laravel'])
        ->assertJsonPath('disabledSources', []);
});

it('saves the settings and keeps the values that the request does not send', function () {
    $this->putJson('/api/settings', ['country' => 'DE', 'disabledSources' => ['jobicy'], 'notificationIntervalHours' => 6])
        ->assertOk()
        ->assertJsonPath('country', 'DE')
        ->assertJsonPath('disabledSources', ['jobicy'])
        ->assertJsonPath('keywordsInclude', ['laravel']);

    $stored = app(SettingsStore::class)->load();

    expect($stored->country)->toBe('DE')
        ->and($stored->disabledSources)->toBe(['jobicy'])
        ->and($stored->notificationIntervalHours)->toBe(6)
        ->and($stored->keywordsInclude)->toBe(['laravel']);
});

it('gives 422 with the InvalidSettings message', function (array $body, string $message) {
    $this->putJson('/api/settings', $body)
        ->assertUnprocessable()
        ->assertExactJson(['message' => $message]);

    expect(app(SettingsStore::class)->load()->country)->toBe('IT');
})->with([
    [['notificationIntervalHours' => 30], 'The notification interval must be from 1 to 24 hours.'],
    [['country' => 'Italy'], 'Country must be ISO 3166-1 alpha-2, got "Italy".'],
    [['workModes' => []], 'Select at least one work mode.'],
    [['disabledSources' => ['nope']], 'Unknown source "nope".'],
]);

it('updates the badge after a change of the settings', function () {
    config(['nativephp-internal.running' => true]);
    ApiHelpers::job('1', ['title' => 'Laravel Developer']);

    $this->putJson('/api/settings', ['keywordsInclude' => ['golang']])->assertOk();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/label') && $request['label'] === '');
});

it('tells the user to leave a minimum empty to turn it off', function (string $field) {
    $this->putJson('/api/settings', [$field => 0])
        ->assertStatus(422)
        ->assertExactJson(['message' => 'A minimum salary or day rate must be above 0. Leave the field empty to turn it off.']);
})->with(['minAnnualSalary', 'minDayRate']);
