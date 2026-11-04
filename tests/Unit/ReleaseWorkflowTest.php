<?php

function releaseWorkflow(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/release.yml');
}

it('pins every action to a full commit SHA', function () {
    preg_match_all('/^\s*-?\s*uses:\s*(\S+)/m', releaseWorkflow(), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $action) {
        expect($action)->toMatch('/^[\w.-]+\/[\w.-]+@[0-9a-f]{40}$/');
    }
});

it('starts on a v tag and on workflow_dispatch', function () {
    expect(releaseWorkflow())->toContain("tags: ['v*']")
        ->toContain('workflow_dispatch:');
});

it('gives write access to the contents only in the draft job', function () {
    $workflow = releaseWorkflow();

    expect(substr_count($workflow, 'contents: write'))->toBe(1)
        ->and(strpos($workflow, 'contents: write'))->toBeGreaterThan(strpos($workflow, "\n  draft:"));
});

it('makes a draft release and never publishes one', function () {
    $workflow = releaseWorkflow();

    expect($workflow)->toContain('gh release create')
        ->toContain('--draft')
        ->not->toContain('--publish')
        ->not->toContain('gh release edit');
});

it('runs the tests and the CA bundle check before the build', function () {
    $workflow = releaseWorkflow();

    expect(strpos($workflow, 'vendor/bin/pest'))->toBeLessThan(strpos($workflow, 'release:check-cacert'))
        ->and(strpos($workflow, 'npm test'))->toBeLessThan(strpos($workflow, 'release:check-cacert'))
        ->and(strpos($workflow, 'release:check-cacert'))->toBeLessThan(strpos($workflow, 'native:build'))
        ->and($workflow)->toContain('needs: test');
});

it('names the uploaded files as latest-mac.yml does, with a dash for each space', function () {
    expect(releaseWorkflow())->toContain('mv "$file" "${file// /-}"');
});

it('checks the signature, Gatekeeper and the notarisation ticket', function () {
    expect(releaseWorkflow())->toContain('codesign --verify --deep --strict')
        ->toContain('spctl -a -vv')
        ->toContain('xcrun stapler validate');
});
