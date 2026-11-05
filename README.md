# Sudo Hire

A macOS menu bar app for developers. It fetches new job posts from company career pages and public job boards, keeps only the jobs that match your filters, and sends one summary notification every few hours.

The main problem it solves: "remote" often does not mean "remote from anywhere". Sudo Hire checks where you can work from, so a developer in Italy does not see a job that is "Remote, US only".

## Status

Early development. Not ready to use yet.

## Features (planned for v1)

- Reads jobs from company career pages (Greenhouse, Lever, Ashby, Personio) and from job boards (LaraJobs, Jobicy, Remotive, Himalayas, We Work Remotely).
- Filters by country eligibility, work mode, salary, day rate, contract type, seniority, keywords and timezone.
- Shows salaries in the original currency and in your currency.
- One summary notification every N hours. No notification when there is nothing new.
- All data stays on your Mac. No accounts, no telemetry.

Every job keeps its source name, its original link and its original apply link.

## Install

Sudo Hire needs a Mac with Apple silicon (M1 and later) and macOS 12 (Monterey) or later. There is no version for a Mac with an Intel processor. To find out, open the Apple menu and choose About This Mac.

1. Download `sudo-hire-<version>-arm64.dmg` from the [releases page](https://github.com/albertoarena/sudo-hire/releases) or from [sudohire.dev](https://sudohire.dev).
2. Open the DMG and drag Sudo Hire to Applications.
3. Start Sudo Hire from Applications. It lives in the menu bar and has no Dock icon.

The app checks for a new version when it starts and then at most once a day. It downloads the new version and installs it when you quit the app. See [PRIVACY.md](PRIVACY.md).

## Stack

Laravel, NativePHP, PHP 8.4, SQLite, Vue 3 with TypeScript, Tailwind CSS.

## Scans in the app

While the app runs, it starts a scan every 5 minutes on its own. It shows a native notification when there are new matches, and a click on it opens the menu bar list. You do not need launchd or cron for the app. They are for scans without the app.

With no country in the settings, a scan stores the jobs but sends no notification.

## Run a scan from the terminal

One scan fetches the sources that are due, filters the new jobs, prints the matches and sends one summary notification when it is due. It does not need the app.

```bash
php artisan hire:scan
```

- `--no-notify`: print the matches only. No notification, and the jobs stay new.
- A second scan that starts while one runs exits at once. So cron or launchd can start a scan every few minutes.
- Each source has its own minimum interval. A scan never fetches a source more often than that.
- The exit code is 0 also when a source fails. The output shows the error kind of each failed source.

A running scan holds a lock in the cache. If the scan process is killed hard (for example with `kill -9`), the lock stays. Then each later scan prints "The [hire:scan] command is already running." and exits, for up to one hour. To remove the lock at once, run `php artisan cache:clear --locks`. A plain `php artisan cache:clear` does not remove it.

### Run a scan from launchd or cron

launchd and cron do not read your shell profile. Use full paths: find the path of the PHP binary with `php -r 'echo PHP_BINARY, PHP_EOL;'` in the project folder (for example `/opt/homebrew/bin/php`). A version manager can put a shim on PATH, so `which php` can give the wrong path. Use the full path of the project folder too.

With launchd, save this file as `~/Library/LaunchAgents/dev.sudohire.scan.plist`. Change the two paths. It runs a scan every 10 minutes and writes the output to `storage/logs/scan.log`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>dev.sudohire.scan</string>
    <key>ProgramArguments</key>
    <array>
        <string>/opt/homebrew/bin/php</string>
        <string>artisan</string>
        <string>hire:scan</string>
    </array>
    <key>WorkingDirectory</key>
    <string>/path/to/sudo-hire</string>
    <key>StartInterval</key>
    <integer>600</integer>
    <key>StandardOutPath</key>
    <string>/path/to/sudo-hire/storage/logs/scan.log</string>
    <key>StandardErrorPath</key>
    <string>/path/to/sudo-hire/storage/logs/scan.log</string>
</dict>
</plist>
```

```bash
launchctl bootstrap gui/$(id -u) ~/Library/LaunchAgents/dev.sudohire.scan.plist
```

To start a scan at once: `launchctl kickstart gui/$(id -u)/dev.sudohire.scan`. To remove the job:

```bash
launchctl bootout gui/$(id -u)/dev.sudohire.scan
```

With cron, run `crontab -e` and add one line. Change the two paths:

```
*/10 * * * * cd /path/to/sudo-hire && /opt/homebrew/bin/php artisan hire:scan >> storage/logs/scan.log 2>&1
```

If the log shows `cURL error 60`, the `php` of launchd or cron uses an old CA bundle. See "cURL error 60 in artisan commands" below. Set the bundle in the `php.ini` of that `php`, because launchd and cron cannot use the `-d` options of your terminal.

## JSON API

The menu bar list and the settings window read and change data through a small JSON API under `/api`. It uses the web middleware, so every write needs the CSRF token from the `csrf-token` meta tag of the page, in the `X-CSRF-TOKEN` header. Errors are JSON with a `message`. Validation errors have the status 422.

| Method and path | What it does |
|---|---|
| `GET /api/list` | The New matches, the "Location not stated" group, Saved, Applied and the counts |
| `PUT /api/jobs/{job}/state` | Sets `state` to `saved`, `applied`, `hidden` or null. The same state again moves the job back to New |
| `GET /api/settings`, `PUT /api/settings` | The settings. A PUT keeps the values that it does not send |
| `GET /api/countries` | The country table, for the country field |
| `GET /api/sources` | Each source with its status and its last error |
| `GET /api/companies`, `POST /api/companies`, `DELETE /api/companies/{company}` | The company list of the ATS reader |
| `GET /api/rates` | The date of the exchange rates and their source |
| `GET /api/status` | The last scan, the next scan and the last notification |
| `GET /api/diagnostics` | The versions and the status of each source, with no personal data |

With `php artisan serve` you can read it with `curl`, e.g. `curl -H 'Accept: application/json' http://127.0.0.1:8000/api/list`.

## Local development

### Two databases

`php artisan` commands use `database/database.sqlite`. The app in `php artisan native:run` uses `database/nativephp.sqlite`. In development, NativePHP migrates that file only when it creates it. After a pull with new migrations, run both:

```bash
php artisan migrate
php artisan native:migrate
```

### cURL error 60 in artisan commands

`cURL error 60: unable to get local issuer certificate` means that your local PHP uses an old CA bundle. The installed app is not affected: it uses the CA bundle of NativePHP.

To check one command, give PHP a current bundle, e.g. the Homebrew one:

```bash
php -d curl.cainfo=/opt/homebrew/etc/ca-certificates/cert.pem \
    -d openssl.cafile=/opt/homebrew/etc/ca-certificates/cert.pem \
    artisan <command>
```

To fix it for good, set `curl.cainfo` and `openssl.cafile` in your `php.ini` (`php --ini` shows the file) to a current bundle. Do not turn off certificate checks.

## Release

For the maintainer. The workflow `.github/workflows/release.yml` builds, signs and notarises the app. It never publishes a release.

1. Make sure that `main` is green. Tag it with the version and push the tag: `git tag v1.0.0 && git push origin v1.0.0`. A release candidate has a dash: `v1.0.0-rc.1`.
2. Approve the run in GitHub Actions. The signing values are in the environment `release`, which waits for the maintainer.
3. The workflow runs the tests and the CA bundle check, then builds the app for Apple silicon. It signs, notarises and staples the app and the DMG, checks them, and uploads the DMG and the updater files to a draft release. A release candidate becomes a draft pre-release.
4. Install the draft DMG on a Mac with Apple silicon. Run the checks in [docs/QA.md](docs/QA.md).
5. Write the release notes in the draft, then publish it. The updater sees only published releases.

The environment `release` holds three secrets and two variables:

- `CSC_LINK` and `CSC_KEY_PASSWORD`: the Developer ID Application certificate as a base64 `.p12` file, and its password.
- `APPLE_API_KEY`: the App Store Connect API key (`.p8`) as base64, for notarisation.
- The variables `APPLE_API_KEY_ID` and `APPLE_API_ISSUER`: the key ID and the issuer ID of that key.

To test the build without the signing values, run the workflow on `main` with "Run workflow". It builds an unsigned app and keeps the DMG as a workflow artifact. It makes no release.

## Documentation

- [Requirements](docs/REQUIREMENTS.md)
- [Decisions](docs/DECISIONS.md)
- [Manual QA](docs/QA.md)
- [Contributing](CONTRIBUTING.md)
- [Privacy](PRIVACY.md)
- [Security](SECURITY.md)

## License

Sudo Hire is released under the [MIT license](LICENSE). The name, the tagline and the art are not covered by the license: see the [trademark and brand policy](TRADEMARK.md).
