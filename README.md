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

## Stack

Laravel, NativePHP, PHP 8.4, SQLite, Vue 3 with TypeScript, Tailwind CSS.

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

## Local development

### cURL error 60 in artisan commands

`cURL error 60: unable to get local issuer certificate` means that your local PHP uses an old CA bundle. The installed app is not affected: it uses the CA bundle of NativePHP.

To check one command, give PHP a current bundle, e.g. the Homebrew one:

```bash
php -d curl.cainfo=/opt/homebrew/etc/ca-certificates/cert.pem \
    -d openssl.cafile=/opt/homebrew/etc/ca-certificates/cert.pem \
    artisan <command>
```

To fix it for good, set `curl.cainfo` and `openssl.cafile` in your `php.ini` (`php --ini` shows the file) to a current bundle. Do not turn off certificate checks.

## Documentation

- [Requirements](docs/REQUIREMENTS.md)
- [Decisions](docs/DECISIONS.md)
- [Manual QA](docs/QA.md)
- [Contributing](CONTRIBUTING.md)
- [Privacy](PRIVACY.md)
- [Security](SECURITY.md)

## License

Sudo Hire is released under the [MIT license](LICENSE). The name, the tagline and the art are not covered by the license: see the [trademark and brand policy](TRADEMARK.md).
