# Privacy

Sudo Hire is a desktop app that runs on your Mac. It has no accounts, no servers and no analytics.

Sudo Hire is in early development. This page describes version 1. It changes when the app changes.

## What the app sends

The app reads public job lists, one public exchange rate file, and the list of Sudo Hire releases. Each request is a plain download. It carries no identifier for you, no device id and no usage data. Like any web server, each site can see the IP address that the request comes from. This project never sees it, because it runs no server of its own.

The app sends requests to these places:

- **The job boards that you keep enabled:** LaraJobs, Jobicy, Remotive, Himalayas and We Work Remotely. Each board is read no more often than its terms allow: at most once an hour, and less often for most boards. The search words are fixed (php, laravel). Your keywords never leave your Mac.
- **The career pages of the companies that you add:** Greenhouse, Lever, Ashby or Personio, one request for each company, at most every 6 hours. The address contains the company name, so that provider can see which of its companies the app reads. It cannot see the other companies on your list.
- **The European Central Bank:** the daily reference exchange rates, at most once a day, to show salaries in your currency.
- **GitHub, for updates:** the app asks the releases page of Sudo Hire on GitHub if there is a new version. It asks when it starts, and then at most once a day. The request is an ordinary download that names the app version. It sends no settings, no keywords and no job data. When there is a new version, the app downloads it from GitHub too.

There are no other network calls. No telemetry, no crash reporting.

When you open a job or an apply link, it opens in your default browser. That is your browser visiting a page, and the app sends nothing with it. The job board or the company then applies its own privacy terms.

## What the app stores

Everything stays on your Mac, in a local SQLite database and in log files:

- The jobs that the app found, with their source, links, location, salary and dates. A job is deleted 30 days after a source last listed it. Saved and applied jobs stay until you remove them
- For each job, the time it was in a notification, so the app does not notify you about it again
- For each job, the time you saw it, and if you saved it, applied to it or hid it
- For each hidden job, a small record with its source keys and a short key made from the company and the title, so the job does not come back
- The list of companies that you added, with the time and the error of their last read
- The daily exchange rates
- A short record of each source run: the time, the number of jobs and the error, if there was one
- Your settings: country, timezone, work modes, keywords, salary, contract types, seniority, blocked companies, disabled sources, display currency and notification interval
- Internal cache, queue and session data that the app needs to run. It contains no data about you
- A log file with warnings and errors, for example a source that could not be read
- The time of the last update check, and a new version that the app downloaded, until it installs when you quit the app

The app reads the timezone of your Mac to set a default. It reads it on your Mac and does not send it.

Nothing is synced anywhere. Deleting the app, its application support folder and its update folders in `~/Library/Caches` removes all of it.

## Notifications

The app shows its summary notification through macOS. macOS can ask for permission to show notifications. You can turn them off in System Settings, under Notifications.

The app asks for nothing else: no contacts, no files, no location, no microphone.

## Premium

A paid Premium source is planned for later, on a separate server. It is not part of version 1. If it is added, this page will say what it sends and what that server stores, before the feature is released.

## Changes

This file lives in the repository. Changes to it are visible in the git history, like any other change.

## Questions

Open an issue on GitHub, or write to **hello@albertoarena.it**.
