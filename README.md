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
