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

it('builds only for Apple silicon', function () {
    $workflow = releaseWorkflow();

    expect(substr_count($workflow, 'native:build mac arm64'))->toBe(2)
        ->and($workflow)->not->toContain('native:build mac all')
        ->not->toContain('native:build mac x64');
});

it('names the DMG sudo-hire-<version>-arm64.dmg and leaves the zip as latest-mac.yml names it', function () {
    $workflow = releaseWorkflow();

    expect($workflow)->toContain('for file in dist/Sudo-Hire-*.dmg dist/Sudo-Hire-*.dmg.blockmap; do')
        ->toContain('mv "$file" "dist/sudo-hire-${name#Sudo-Hire-}"')
        ->not->toContain('dist/Sudo-Hire-*.zip');
});

it('uses the release environment only for a tag, so a manual run stays unsigned', function () {
    expect(releaseWorkflow())->toContain("environment: \${{ github.event_name == 'push' && 'release' || '' }}");
});

it('notarises with an App Store Connect API key and no Apple ID', function () {
    $workflow = releaseWorkflow();

    expect($workflow)->not->toContain('NATIVEPHP_APPLE_')
        ->not->toContain('APPLE_APP_SPECIFIC_PASSWORD')
        ->toContain('APPLE_API_KEY: ${{ env.APPLE_API_KEY_PATH }}')
        ->toContain('APPLE_API_KEY_ID: ${{ secrets.APPLE_API_KEY_ID }}')
        ->toContain('APPLE_API_ISSUER: ${{ secrets.APPLE_API_ISSUER }}')
        ->not->toContain('vars.');
});

it('checks every signing value before the build', function () {
    $workflow = releaseWorkflow();
    $check = substr($workflow, strpos($workflow, 'name: Check the signing and notarisation values'));

    foreach (['CSC_LINK', 'CSC_KEY_PASSWORD', 'APPLE_API_KEY', 'APPLE_API_KEY_ID', 'APPLE_API_ISSUER'] as $name) {
        expect($check)->toContain($name);
    }

    expect(strpos($workflow, 'name: Check the signing and notarisation values'))->toBeLessThan(strpos($workflow, 'name: Build, sign and notarise'));
});

it('writes the API key to the runner temp folder and always removes the key and the keychain', function () {
    $workflow = releaseWorkflow();
    $cleanup = substr($workflow, strpos($workflow, 'name: Remove the key and the keychain'), 400);

    expect($workflow)->toContain('"$RUNNER_TEMP/notary-key.p8"')
        ->toContain('umask 077')
        ->and($cleanup)->toContain('if: always()')
        ->toContain('rm -f "$RUNNER_TEMP/notary-key.p8"')
        ->toContain('security delete-keychain');
});

it('signs, notarises and staples the DMG after the build', function () {
    $workflow = releaseWorkflow();
    $step = substr($workflow, strpos($workflow, 'name: Sign, notarise and staple the DMG'));

    expect($step)->toContain('codesign --force --timestamp --sign')
        ->toContain('xcrun notarytool submit')
        ->toContain('--wait')
        ->and(strpos($workflow, 'name: Sign, notarise and staple the DMG'))->toBeGreaterThan(strpos($workflow, 'name: Build, sign and notarise'))
        ->and(strpos($workflow, 'name: Sign, notarise and staple the DMG'))->toBeLessThan(strpos($workflow, 'name: Check the signature, Gatekeeper and the notarisation ticket'));
});

it('makes the signing keychain itself and passes it to electron-builder', function () {
    $workflow = releaseWorkflow();
    $import = strpos($workflow, 'name: Import the signing certificate');
    $build = strpos($workflow, 'name: Build, sign and notarise');
    $importStep = substr($workflow, $import, $build - $import);
    $buildStep = substr($workflow, $build, strpos($workflow, 'name: Build unsigned') - $build);

    expect($import)->toBeLessThan($build)
        ->and($importStep)->toContain('security set-key-partition-list -S apple-tool:,apple: -s -k "$keychain_password" "$keychain"')
        ->toContain('security list-keychains -d user -s "$keychain"')
        ->toContain('echo "CSC_KEYCHAIN=$keychain" >> "$GITHUB_ENV"')
        ->toContain('echo "CSC_NAME=$identity" >> "$GITHUB_ENV"')
        ->and($buildStep)->not->toContain('CSC_LINK')
        ->not->toContain('CSC_KEY_PASSWORD');
});

it('signs the DMG from the same keychain as the app', function () {
    $workflow = releaseWorkflow();
    $step = substr($workflow, strpos($workflow, 'name: Sign, notarise and staple the DMG'));

    expect($step)->toContain('--sign "Developer ID Application: $CSC_NAME" --keychain "$CSC_KEYCHAIN"')
        ->and(substr_count($workflow, 'security create-keychain'))->toBe(1);
});

it('fails when the build makes no DMG, zip or latest-mac.yml', function () {
    $workflow = releaseWorkflow();
    $step = substr($workflow, strpos($workflow, 'name: List the build'), 500);

    expect($step)->toContain("for pattern in '*.dmg' '*.zip' 'latest-mac.yml'; do")
        ->toContain('exit 1');
});

it('builds on a fixed macOS image', function () {
    expect(releaseWorkflow())->toContain('runs-on: macos-15')
        ->not->toContain('runs-on: macos-latest');
});

it('runs every stapler call of the workflow through the retry script', function () {
    preg_match_all('/^.*xcrun stapler (staple|validate).*$/m', releaseWorkflow(), $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $line) {
        expect($line)->toContain('scripts/retry.sh 5 ');
    }
});

it('checks the DMG itself, and not only the app in it', function () {
    expect(releaseWorkflow())->toContain('spctl -a -vv -t open --context context:primary-signature "$dmg"')
        ->toContain('scripts/retry.sh 5 xcrun stapler validate "$dmg"');
});

it('marks a tag with a dash as a pre-release', function () {
    $workflow = releaseWorkflow();

    expect($workflow)->toContain('if [[ "$GITHUB_REF_NAME" == *-* ]]; then')
        ->toContain('--prerelease');
});
