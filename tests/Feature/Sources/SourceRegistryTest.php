<?php

use App\Settings\Settings;
use App\Sources\Ats\AtsSource;
use App\Sources\SourceRegistry;
use App\Sources\UnknownSource;
use Tests\Fakes\FakeSource;

it('lists every v1 source once, by its key', function () {
    $registry = new SourceRegistry;

    expect($registry->keys())->toBe(['ats', 'larajobs', 'jobicy', 'remotive', 'himalayas', 'weworkremotely'])
        ->and($registry->source('ats'))->toBeInstanceOf(AtsSource::class);

    foreach ($registry->keys() as $key) {
        expect($registry->source($key)->key())->toBe($key);
    }
});

it('refuses two sources with the same key', function () {
    new SourceRegistry([new FakeSource, new FakeSource]);
})->throws(LogicException::class, 'Two sources have the key "fake".');

it('refuses an unknown key', function () {
    (new SourceRegistry)->source('remoteok');
})->throws(UnknownSource::class, 'Unknown source "remoteok".');

it('rejects an unknown key in a list of disabled sources', function () {
    (new SourceRegistry)->assertKnown(['larajobs', 'remoteok']);
})->throws(UnknownSource::class, 'Unknown source "remoteok".');

it('accepts a list of known keys', function () {
    (new SourceRegistry)->assertKnown(['larajobs', 'jobicy']);

    expect(true)->toBeTrue();
});

it('leaves out the disabled sources', function () {
    $settings = Settings::fromArray(['disabledSources' => ['jobicy', 'himalayas']]);

    $keys = array_map(fn ($source) => $source->key(), (new SourceRegistry)->enabled($settings));

    expect($keys)->toBe(['ats', 'larajobs', 'remotive', 'weworkremotely']);
});
