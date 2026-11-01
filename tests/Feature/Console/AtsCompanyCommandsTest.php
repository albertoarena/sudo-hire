<?php

use App\Models\AtsCompany;
use App\Sources\Ats\Ats;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds a company with the default display name', function () {
    $this->artisan('ats:add', ['entry' => 'lever:spotify'])
        ->expectsOutputToContain('Added lever:spotify (Spotify).')
        ->assertSuccessful();

    $company = AtsCompany::sole();

    expect($company->ats)->toBe(Ats::Lever)
        ->and($company->slug)->toBe('spotify')
        ->and($company->display_name)->toBe('Spotify')
        ->and($company->enabled)->toBeTrue();
});

it('adds a company with a display name', function () {
    $this->artisan('ats:add', ['entry' => 'greenhouse:gitlab', '--name' => 'GitLab'])
        ->expectsOutputToContain('Added greenhouse:gitlab (GitLab).')
        ->assertSuccessful();

    expect(AtsCompany::sole()->display_name)->toBe('GitLab');
});

it('refuses to add a company twice', function () {
    $this->artisan('ats:add', ['entry' => 'lever:spotify'])->assertSuccessful();

    $this->artisan('ats:add', ['entry' => 'lever:spotify'])
        ->expectsOutputToContain('lever:spotify is already in the list.')
        ->assertFailed();

    expect(AtsCompany::count())->toBe(1);
});

it('refuses an unknown ATS', function () {
    $this->artisan('ats:add', ['entry' => 'workday:acme'])
        ->expectsOutputToContain('Unknown ATS "workday"')
        ->assertFailed();

    expect(AtsCompany::count())->toBe(0);
});

it('refuses an empty slug', function () {
    $this->artisan('ats:add', ['entry' => 'greenhouse:'])
        ->expectsOutputToContain('Use the form <ats>:<slug>')
        ->assertFailed();

    expect(AtsCompany::count())->toBe(0);
});

it('removes a company', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify']);
    AtsCompany::create(['ats' => Ats::Ashby, 'slug' => 'ashby', 'display_name' => 'Ashby']);

    $this->artisan('ats:remove', ['entry' => 'lever:spotify'])
        ->expectsOutputToContain('Removed lever:spotify.')
        ->assertSuccessful();

    expect(AtsCompany::sole()->slug)->toBe('ashby');
});

it('fails to remove a company that is not in the list', function () {
    $this->artisan('ats:remove', ['entry' => 'lever:spotify'])
        ->expectsOutputToContain('lever:spotify is not in the list.')
        ->assertFailed();
});

it('lists the companies with the last result of each', function () {
    AtsCompany::create(['ats' => Ats::Greenhouse, 'slug' => 'gitlab', 'display_name' => 'GitLab', 'last_checked_at' => '2026-10-29 08:00:00', 'last_job_count' => 3]);
    AtsCompany::create(['ats' => Ats::Personio, 'slug' => 'nobody', 'display_name' => 'Nobody', 'last_checked_at' => '2026-10-29 09:00:00', 'last_error' => 'HTTP 404', 'last_error_kind' => 'http']);
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify', 'enabled' => false]);

    $this->artisan('ats:list')
        ->expectsTable(
            ['Entry', 'Name', 'Enabled', 'Last checked', 'Last result'],
            [
                ['greenhouse:gitlab', 'GitLab', 'yes', '2026-10-29 08:00 UTC', '3 jobs'],
                ['personio:nobody', 'Nobody', 'yes', '2026-10-29 09:00 UTC', 'Error (http): HTTP 404'],
                ['lever:spotify', 'Spotify', 'no', 'never', 'not checked yet'],
            ],
        )
        ->assertSuccessful();
});

it('says when the list is empty', function () {
    $this->artisan('ats:list')
        ->expectsOutputToContain('No companies yet. Add one with: php artisan ats:add <ats>:<slug>')
        ->assertSuccessful();
});
