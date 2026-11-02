<?php

use App\Models\AtsCompany;
use App\Sources\Ats\Ats;
use App\Sources\RunErrorKind;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the companies with their last result', function () {
    AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'gitlab', 'display_name' => 'GitLab', 'last_checked_at' => CarbonImmutable::parse('2026-11-02T08:00:00Z'), 'last_job_count' => 12]);
    AtsCompany::query()->create(['ats' => Ats::Lever, 'slug' => 'nope', 'display_name' => 'Nope', 'last_checked_at' => CarbonImmutable::parse('2026-11-02T08:01:00Z'), 'last_error' => 'HTTP 404', 'last_error_kind' => RunErrorKind::Http]);
    AtsCompany::query()->create(['ats' => Ats::Ashby, 'slug' => 'old', 'display_name' => 'Old', 'last_error' => 'Something failed']);

    $companies = $this->getJson('/api/companies')->assertOk()->json();

    expect($companies[0])->toBe([
        'id' => $companies[0]['id'],
        'provider' => 'greenhouse',
        'slug' => 'gitlab',
        'name' => 'GitLab',
        'enabled' => true,
        'lastCheckedAt' => '2026-11-02T08:00:00Z',
        'lastJobCount' => 12,
        'lastError' => null,
    ])
        ->and($companies[1]['lastError'])->toBe(['message' => 'HTTP 404', 'kind' => 'http'])
        // A row from before the error kind has no kind: it shows "other", as ats:list does.
        ->and($companies[2]['lastError'])->toBe(['message' => 'Something failed', 'kind' => 'other']);
});

it('adds a company with the ats:add rules', function () {
    $this->postJson('/api/companies', ['provider' => 'Greenhouse', 'slug' => 'acme-corp'])
        ->assertCreated()
        ->assertJsonPath('provider', 'greenhouse')
        ->assertJsonPath('slug', 'acme-corp')
        ->assertJsonPath('name', 'Acme Corp');

    $this->postJson('/api/companies', ['provider' => 'lever', 'slug' => 'globex', 'name' => 'Globex Inc'])
        ->assertCreated()
        ->assertJsonPath('name', 'Globex Inc');

    expect(AtsCompany::query()->count())->toBe(2);
});

it('gives 422 with the ats:add message', function (array $body, string $message) {
    AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'acme', 'display_name' => 'Acme']);

    $this->postJson('/api/companies', $body)
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);

    expect(AtsCompany::query()->count())->toBe(1);
})->with([
    'already in the list' => [['provider' => 'greenhouse', 'slug' => 'acme'], 'greenhouse:acme is already in the list.'],
    'unknown provider' => [['provider' => 'workday', 'slug' => 'acme'], 'Unknown ATS "workday". Use one of: greenhouse, lever, ashby, personio.'],
    'bad slug' => [['provider' => 'lever', 'slug' => 'a b'], 'The slug "a b" can only have letters, digits, dots, dashes and underscores.'],
]);

it('gives 422 when the provider or the slug is missing', function () {
    $this->postJson('/api/companies', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['provider', 'slug']);
});

it('removes a company', function () {
    $company = AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'acme', 'display_name' => 'Acme']);

    $this->deleteJson("/api/companies/{$company->id}")->assertNoContent();

    expect(AtsCompany::query()->count())->toBe(0);
});

it('gives 404 with a message for an unknown company', function () {
    $this->deleteJson('/api/companies/999')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Company not found.']);
});
