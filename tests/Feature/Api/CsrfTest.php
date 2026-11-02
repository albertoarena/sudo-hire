<?php

use App\Models\AtsCompany;
use App\Sources\Ats\Ats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Laravel skips the CSRF check in unit tests. Run these tests as the local app.
    app()->detectEnvironment(fn () => 'local');
});

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

it('refuses a write with no CSRF token', function (string $method, string $uri, array $body) {
    AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'acme', 'display_name' => 'Acme']);
    $job = ApiHelpers::job('1');

    $this->json($method, str_replace('{job}', (string) $job->id, $uri), $body)
        ->assertStatus(419)
        ->assertJsonPath('message', 'CSRF token mismatch.');
})->with([
    'state' => ['PUT', '/api/jobs/{job}/state', ['state' => 'saved']],
    'settings' => ['PUT', '/api/settings', ['country' => 'DE']],
    'add a company' => ['POST', '/api/companies', ['provider' => 'lever', 'slug' => 'globex']],
    'remove a company' => ['DELETE', '/api/companies/1', []],
]);

it('puts the CSRF token of the session in the page', function () {
    $html = $this->withSession(['_token' => 'page-token'])->get('/')->assertOk()->getContent();

    expect($html)->toContain('<meta name="csrf-token" content="page-token">');
});

it('accepts a write with the CSRF token of the session', function () {
    ApiHelpers::settings();

    $this->withSession(['_token' => 'page-token'])
        ->putJson('/api/settings', ['country' => 'DE'], ['X-CSRF-TOKEN' => 'page-token'])
        ->assertOk()
        ->assertJsonPath('country', 'DE');
});

it('reads with no CSRF token', function () {
    $this->getJson('/api/list')->assertOk();
});
