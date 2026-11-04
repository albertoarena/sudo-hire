// Turns a release tag into the app version: v1.0.0 gives 1.0.0, v1.0.0-rc.1 gives 1.0.0-rc.1.
// The release workflow (.github/workflows/release.yml) runs it with the tag name and
// sets NATIVEPHP_APP_VERSION from the output. It refuses anything that is not a v tag.

import { fileURLToPath } from 'node:url';

const tag = /^(?:refs\/tags\/)?v(\d+\.\d+\.\d+(?:-[0-9A-Za-z]+(?:\.[0-9A-Za-z]+)*)?)$/;

export function versionFromTag(ref) {
    const match = tag.exec(ref ?? '');

    if (!match) {
        throw new Error(`not a release tag: ${ref}`);
    }

    return match[1];
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    try {
        console.log(versionFromTag(process.argv[2]));
    } catch (error) {
        console.error(`release-version: ${error.message}`);
        process.exit(1);
    }
}
