<?php

use App\Settings\SystemCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fakeCountry(?string $code): void
{
    app()->instance(SystemCountry::class, new class($code) extends SystemCountry
    {
        public function __construct(private readonly ?string $fake) {}

        public function code(): ?string
        {
            return $this->fake;
        }
    });
}

it('gives the country of the Mac as the preset', function () {
    fakeCountry('IT');

    $this->getJson('/api/country-preset')->assertOk()->assertExactJson(['country' => 'IT']);
});

it('gives no preset when the Mac region is not a country', function () {
    fakeCountry(null);

    $this->getJson('/api/country-preset')->assertOk()->assertExactJson(['country' => null]);
});

it('does not store the preset', function () {
    fakeCountry('IT');

    $this->getJson('/api/country-preset')->assertOk();

    $this->getJson('/api/settings')->assertOk()->assertJsonPath('country', null);
});
