# Manual QA

Location: `docs/QA.md` (committed).

Checks that tests cannot cover: menu bar, notifications, windows, timing, permissions, signing. A human runs them on a Mac before merging the pull request that added them.

Add entries in date order. Do not delete entries: mark them passed or failed, with the date.

## Template

```
## Mx: <short title>

- Added: YYYY-MM-DD, branch <branch>
- Why manual: <what tests cannot see>
- Setup: <state before the check, e.g. "fresh install, no settings">
- Steps:
  1. ...
  2. ...
- Expected: <what the human must see>
- Result: not run | passed YYYY-MM-DD | failed YYYY-MM-DD (<short note>)
```

## Before every merge (Mac)

1. Check out the branch.
2. `npm run build`, then `php artisan native:run`.
3. Run every entry for this milestone with result "not run".
4. Update each result.
5. If one fails, send the failure back to the session that made the change.

## Entries

## M1: App starts as a menu bar icon

- Added: 2026-10-28, branch m1/scaffold
- Why manual: tests only fake the NativePHP client. They cannot see the macOS menu bar, the dock or a window.
- Setup: fresh checkout, `composer install`, `npm ci`, `cp .env.example .env`, `php artisan key:generate`.
- Steps:
  1. `npm run build`
  2. `php artisan native:run`
  3. Look at the menu bar, the dock and the screen.
  4. Hover the new menu bar icon.
  5. Click the icon.
  6. Click somewhere else on the screen.
- Expected: no window opens on start. No Sudo Hire icon in the dock. A new icon in the menu bar shows the tooltip "Sudo Hire". The click opens a small panel (about 400 x 600) with the text "Sudo Hire". The panel closes when you click elsewhere.
- Result: passed 2026-10-28

## M1: Cloud setup script in a real cloud environment

- Added: 2026-10-28, branch m1/scaffold
- Why manual: the script was tested here in a scratch clone with a fake home folder, not in the real cloud container.
- Setup: a cloud environment for this repository with `scripts/cloud-setup.sh` pasted into its setup script.
- Steps:
  1. Start a new cloud session on this repository.
  2. Run `git config commit.gpgsign`, `echo $COMPOSER_ALLOW_SUPERUSER` and `ls vendor node_modules | head -2`.
  3. Run `grep -c COMPOSER_ALLOW_SUPERUSER ~/.bashrc`.
- Expected: `false`, `1`, both folders exist, and the count is `1` (the line is not added twice).
- Result: failed 2026-10-29 (script exits before the clone; replaced by an environment setup script)

## Prep: Cloud environment setup script

- Added: 2026-10-29, branch prep/cloud-setup-env
- Why manual: tests run the script with temporary home folders, not in the real cloud container.
- Setup: the Sudo Hire cloud environment with `scripts/cloud-setup.sh` as its setup script, the `GIT_*` variables and `COMPOSER_ALLOW_SUPERUSER=1`.
- Steps:
  1. Start a new cloud session on this repository, with this environment.
  2. Check that the start panel shows "Ran setup script" in green.
  3. In the cloud session, run `git var GIT_AUTHOR_IDENT`, `git config commit.gpgsign`, `echo $COMPOSER_ALLOW_SUPERUSER` and `cat ~/.claude/settings.json`.
  4. In the cloud session, run `composer install --no-interaction --ignore-platform-req=php`.
- Expected: the name and email from `GIT_AUTHOR_NAME` and `GIT_AUTHOR_EMAIL`, `false`, `1`, a JSON with an `attribution` block, and a Composer install that works. Only PHPStan may need the zip workaround in `AGENTS.md`.
- Result: partly passed 2026-10-29, in the M3 cloud session (branch m3/remotive-himalayas-wwr). Step 2 was not visible from inside the session.
  - `git config user.email` gave `noreply@anthropic.com`: `~/.gitconfig` has the Claude name and email. `git var GIT_AUTHOR_IDENT` and `git var GIT_COMMITTER_IDENT` gave the owner's name and email, because the `GIT_*` variables win over the file. Commits get the correct identity.
  - `git config commit.gpgsign` gave `false`. `echo $COMPOSER_ALLOW_SUPERUSER` gave `1`. `~/.claude/settings.json` had the `attribution` block.
  - `composer install --no-interaction` installed 134 of 135 packages from git source. `phpstan/phpstan` failed with HTTP 403 on its `api.github.com` zipball. The zip workaround in `AGENTS.md` fixed it.

## M2: Source terms and endpoints

- Added: 2026-10-29, branch m2/ats-larajobs-jobicy
- Why manual: the cloud session proxy blocks all six sources and their terms pages (403).
- Setup: a browser on a Mac with normal internet access.
- Steps:
  1. Read the terms of use or API terms of Greenhouse (job board API), Lever (postings API), Ashby (posting API), Personio (XML feed), LaraJobs and Jobicy.
  2. For each source, note the attribution, the rate limit and any restriction.
  3. Open each endpoint in the browser: `https://boards-api.greenhouse.io/v1/boards/gitlab/jobs?content=true`, `https://api.lever.co/v0/postings/spotify?mode=json`, `https://api.ashbyhq.com/posting-api/job-board/ashby?includeCompensation=true`, `https://ottonova.jobs.personio.de/xml`, `https://larajobs.com/feed`, `https://jobicy.com/api/v2/remote-jobs?count=50&tag=php`.
  4. Update the "Source terms" and "Checked on" lines of the three M2 source terms entries in `docs/DECISIONS.md`.
- Expected: each endpoint returns jobs in the format of its fixture. No terms page forbids the use in the app, or the decision entry says what must change.
- Result: passed 2026-10-29

## M2: ATS reader and job boards with live data

- Added: 2026-10-29, branch m2/ats-larajobs-jobicy
- Why manual: tests use fixtures only. The cloud session cannot reach the sources.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate`.
- Steps:
  1. Run `php artisan ats:add greenhouse:gitlab --name=GitLab`, `php artisan ats:add lever:spotify`, `php artisan ats:add ashby:ashby` and `php artisan ats:add personio:ottonova`.
  2. Run `php artisan ats:add personio:no-such-company-sudo-hire`.
  3. Run `php artisan tinker --execute='$b = (new App\Sources\NormaliseItems)(app(App\Sources\Ats\AtsSource::class)); dump(count($b->jobs), $b->skipped, $b->skipReasons);'`
  4. Run `php artisan ats:list`.
  5. Run step 3 again, then `php artisan ats:list` again.
  6. Run `php artisan tinker --execute='foreach ([App\Sources\LaraJobs\LaraJobsSource::class, App\Sources\Jobicy\JobicySource::class] as $s) { $b = (new App\Sources\NormaliseItems)(new $s); dump($s, count($b->jobs), $b->skipped); }'`
  7. Open the `apply_url` of one job from each source in a browser. To see one, add `dump($b->jobs[0]->applyUrl);` to the commands of step 3 and step 6.
- Expected: step 3 shows more than 0 jobs and 0 skipped. In step 4, the four companies show "N jobs" and the unknown company shows "Error: HTTP 404". In step 5, the "Last checked" times do not change, because the 6 hour interval skips every company. Step 6 shows more than 0 jobs for both boards. Each apply link opens the job at the company or at the board.
- Result: passed 2026-10-29 (the LaraJobs job link forwards to the employer's apply form)

## M2: Lever salary range with a real posting

- Added: 2026-10-29, branch m2/ats-larajobs-jobicy
- Why manual: no Lever fixture has a `salaryRange`. The interval values come from the Lever API documentation, not from a real response.
- Setup: a browser on a Mac.
- Steps:
  1. Open `https://api.lever.co/v0/postings/<slug>?mode=json` for some Lever companies that post salaries (for example US companies).
  2. Find a posting with a `salaryRange` field.
  3. Save the response, trimmed to that posting, as `tests/Fixtures/lever/postings-salary.json`.
- Expected: `salaryRange` has `min`, `max`, `currency` and `interval`. The interval is one of `per-year-salary`, `per-month-salary`, `per-day-wage`, `per-hour-wage`. If not, add a test with the new fixture and fix `LeverReader`.
- Result: passed 2026-10-29 (real postings use `per-year-salary` and `per-hour-wage`; `per-month-salary` and `per-day-wage` not seen; fixture `lever/postings-salary.json`)

## M3: Source terms and endpoints

- Added: 2026-10-29, branch m3/remotive-himalayas-wwr
- Why manual: the cloud session cannot reach Remotive, Himalayas or We Work Remotely, or their terms pages.
- Setup: a browser on a Mac with normal internet access.
- Steps:
  1. Read the Remotive API terms (`https://remotive.com/api-documentation`), the Himalayas API terms (`https://himalayas.app/api`) and the We Work Remotely terms of service.
  2. For each source, note the attribution, the rate limit and any restriction on the use in a desktop app.
  3. Open each endpoint in the browser: `https://remotive.com/api/remote-jobs?search=php`, `https://himalayas.app/jobs/api/search?q=php&page=1`, `https://himalayas.app/jobs/api/search?q=php&page=2`, `https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss`.
  4. Update the "Source terms" and "Checked on" lines of the three M3 source terms entries in `docs/DECISIONS.md`.
- Expected: each endpoint returns jobs in the format of its fixture. Himalayas page 2 has other jobs than page 1, 20 to a page. No terms page forbids the use in the app, or the decision entry says what must change.
- Result: passed 2026-10-29 (all endpoints return jobs in the fixture format; Himalayas pages 1 and 2 have 20 different jobs each; the three terms are in the M3 source terms entries; the Himalayas use in a single-user app is accepted by the project owner)

## M3: Remotive, Himalayas and We Work Remotely with live data

- Added: 2026-10-29, branch m3/remotive-himalayas-wwr
- Why manual: tests use fixtures only. The cloud session cannot reach the sources.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`.
- Steps:
  1. Run `php artisan tinker --execute='foreach ([App\Sources\Remotive\RemotiveSource::class, App\Sources\Himalayas\HimalayasSource::class, App\Sources\WeWorkRemotely\WeWorkRemotelySource::class] as $s) { $b = (new App\Sources\NormaliseItems)(new $s); dump($s, count($b->jobs), $b->skipped, $b->skipReasons, $b->jobs[0]->applyUrl ?? null); }'`
  2. Open the three apply links from step 1 in a browser.
  3. Run `php artisan tinker --execute='$b = (new App\Sources\NormaliseItems)(new App\Sources\Himalayas\HimalayasSource, now()->subDay()); dump(count($b->jobs));'`
- Expected: step 1 shows more than 0 jobs for each source and 0 skipped, or skip reasons that make sense. The Himalayas apply link ends with `?utm_source=api`. Each apply link opens the job page at the board. Step 3 shows fewer or the same number of Himalayas jobs as step 1. Run step 1 at most once for Remotive on a day: it allows 4 requests a day.
- Result: passed 2026-10-29 (step 1: Remotive 12 jobs, Himalayas 151, We Work Remotely 8, 0 skipped; the three apply links open the job pages, Himalayas with `?utm_source=api`; step 3: 94 Himalayas jobs)

## M5: ECB rates terms and live fetch

- Added: 2026-10-29, branch m5/salary-parser
- Why manual: the cloud session cannot reach the ECB website. Tests use the fixture `tests/Fixtures/ecb/eurofxref-daily.xml` only.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate`.
- Steps:
  1. Read the ECB page on the euro foreign exchange reference rates and the ECB copyright notice on reuse.
  2. Run `php artisan tinker --execute='$r = app(App\Currency\ExchangeRates::class); dump($r->refresh(), $r->rateDate()?->toDateString(), $r->converter()->convert(100, "EUR", "USD"));'`
  3. Run the same command again.
- Expected: the terms allow the use in the app when the ECB is named as the source, or the decision entry "Exchange rates come from the ECB directly" says what must change. Step 2 shows `true`, the date of the last TARGET working day and a value near 100 times the USD rate. Step 3 shows `false` and the same date.
- Result: passed 2026-10-30, on a Mac, on main after the merge of #11.
  - Terms: the ECB copyright notice allows free reuse when the ECB is cited as the source. Data that the user changes (e.g. calculations) must say so. The rates page says the rates are for information only.
  - Step 2 first gave `false`, `null`, `null`: cURL error 60 in `storage/logs/laravel.log`. The local PHP CA bundle had no root for the ECB certificate. With `php -d curl.cainfo=<Homebrew cert.pem> -d openssl.cafile=<Homebrew cert.pem>`, step 2 gave `true`, `2026-10-29`, `111.3`. Step 3 gave `false`, `2026-10-29`. See the "Local development" section in `README.md`.

## M6a: Default timezone matches System Settings

- Added: 2026-10-30, branch m6a/storage-runner
- Why manual: tests use a fake `/etc/localtime` link. The cloud container is Linux.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate:fresh`.
- Steps:
  1. Open System Settings, General, Date & Time. Note the time zone.
  2. Run `php artisan tinker --execute='dump(app(App\Settings\SettingsStore::class)->load()->timezone);'`
  3. In System Settings, set another time zone by hand (for example "Tokyo"). Run step 2 again. Set the time zone back.
- Expected: step 2 shows the IANA name of the time zone from step 1, for example `Europe/Rome`, not `UTC`. Step 3 shows the new time zone, for example `Asia/Tokyo`.
- Result: passed 2026-10-31, on a Mac, on main after the merge of #16. Step 2 gave `Europe/Rome`. Step 3 gave `Asia/Tokyo`. No settings were stored yet, so each load read the Mac time zone.

## M6a: Source runner with live data

- Added: 2026-10-30, branch m6a/storage-runner
- Why manual: tests use fixtures only. The cloud session cannot reach the sources.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate:fresh`, `php artisan ats:add greenhouse:gitlab`.
- Steps:
  1. Run `php artisan tinker --execute='$s = app(App\Settings\SettingsStore::class)->load(); foreach (app(App\Sources\SourceRunner::class)->run($s) as $r) { dump($r->only("source", "items_fetched", "items_skipped", "unknown_eligibility_share", "unknown_salary_share", "error_kind", "error")); } dump(App\Models\Job::count(), App\Models\AlsoOnSource::count());'`
  2. Run the same command again at once.
- Expected: step 1 shows one row for each of the 6 sources. Each row has `error_kind` null, or an error that makes sense. The shares are from 0 to 1. Step 2 shows no rows: no source is due yet. Run step 1 at most once for Remotive on a day: it allows 4 requests a day.
- Result: passed 2026-10-31, on a Mac, on main after the merge of #16, with the Homebrew CA bundle (see `README.md`, "Local development").
  - Setup: `php artisan migrate:fresh` is necessary. An older local database has no `expires_at` column, and step 1 then fails with an SQL error for each source.
  - Step 1 on 2026-10-30: all 6 sources, `error_kind` null, 0 skipped. Items: ats 405 (4 companies), LaraJobs 5, Jobicy 0, Remotive 12, Himalayas 151, We Work Remotely 9. 582 jobs, 0 "also on" rows.
  - Step 1 again on 2026-10-31, when all sources were due: all 6 sources, `error_kind` null, 0 skipped. Items: ats 410, LaraJobs 5, Jobicy 0, Remotive 13, Himalayas 38 (only jobs after the last run), We Work Remotely 11. 597 jobs, 0 "also on" rows.
  - Step 2: no rows, 597 jobs, 0 "also on" rows.
  - Jobicy 0 items: the Jobicy API itself gives "No jobs found" for `tag=php`, also on the new `/api/jobs` endpoint. Without a tag it gives 100 jobs. Not a fault in the source.

## M6c: Scan shows a macOS notification

- Added: 2026-11-01, branch m6c/scan
- Why manual: tests use a fake notifier. The cloud container has no `osascript` and no macOS notifications.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `php artisan migrate`. If the local PHP gives cURL error 60, use the fix in `README.md`, "cURL error 60 in artisan commands".
- Steps:
  1. Run `php artisan tinker --execute='App\Models\Job::query()->update(["notified_at" => null]); App\Models\SentNotification::query()->delete();'`
  2. Run `php artisan hire:scan`.
  3. Run `php artisan hire:scan` again at once.
  4. Run `php artisan hire:scan --no-notify`.
- Expected: step 2 prints a table of matches, a "Location not stated" count if there are such jobs, one line for each source run, and "Notification sent: N new jobs: ...". A macOS notification with the same text and the title "Sudo Hire" appears. macOS can first ask to allow notifications for Script Editor: allow them, then run steps 1 and 2 again. Step 3 shows no notification. It prints "No notification: 0 new matches." or "Notification not due: ...". Step 4 prints "Notification off (--no-notify)." and shows no notification. If step 2 finds 0 matches, set fewer filters and run steps 1 and 2 again.
- Result: passed 2026-11-01, on a Mac, on branch m6c/scan.

## M6c: Scan from launchd or cron writes its log

- Added: 2026-11-01, branch m6c/scan
- Why manual: the cloud container has no launchd, and its cron does not run as a Mac user.
- Setup: as for "M6c: Scan shows a macOS notification".
- Steps:
  1. Set up the scan with launchd as in `README.md`, "Run a scan from launchd or cron". Use the full path of the PHP binary and of the project folder.
  2. Run `launchctl kickstart gui/$(id -u)/dev.sudohire.scan`, or wait for one interval (10 minutes in the example).
  3. Read `storage/logs/scan.log`.
  4. Make the sources due: run `php artisan tinker --execute='App\Models\SourceRun::query()->each(fn ($r) => $r->update(["started_at" => $r->started_at->subDay()]));'`. Then run `php artisan hire:scan --no-notify & sleep 1; launchctl kickstart gui/$(id -u)/dev.sudohire.scan; wait` and read the end of `storage/logs/scan.log`.
  5. Remove the job as in the README. Optional: do steps 1 to 3 with the cron line instead of launchd.
- Expected: step 3 shows the output of at least one scan: the matches or "No new matches.", and the source lines. No "command not found" and no cURL error 60. `launchctl print gui/$(id -u)/dev.sudohire.scan` shows `last exit code = 0`. In step 4 the scan from launchd starts while the terminal scan runs, and the log shows "The [hire:scan] command is already running.": the scan is isolated. A scan with no due source ends in under a second, so step 4 first makes the sources due.
- Result: passed 2026-11-01, on a Mac, on branch m6c/scan, with launchd. Cron was not run. The scan from launchd fetched all sources (LaraJobs 5 items, Remotive 12, Himalayas 38, We Work Remotely 11, ATS and Jobicy 0) and exited with code 0. The first run of step 4 did not overlap, because no source was due. Step 4 now makes the sources due first.

## M7: The app scans on its own and shows a native notification

- Added: 2026-11-01, branch m7/native-scheduler
- Why manual: tests fake the internal API. The cloud container cannot run the app, the scheduler or macOS notifications.
- Setup: the branch checked out on a Mac, `composer install`, `.env` from `.env.example`, `php artisan key:generate`, `npm run build`. Run `php artisan native:migrate`: in development, NativePHP migrates `database/nativephp.sqlite` only when it creates the file. In development the app uses `database/nativephp.sqlite`. The commands below use it with `DB_DATABASE`.
- Steps:
  1. Run `php artisan native:run` once, wait until the menu bar icon shows, then quit the app. This creates `database/nativephp.sqlite`.
  2. Store a country, and make the jobs new: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='$s = app(App\Settings\SettingsStore::class); $s->save(App\Settings\Settings::fromArray(array_merge($s->load()->toArray(), ["country" => "IT"]))); App\Models\Job::query()->update(["notified_at" => null]); App\Models\SentNotification::query()->delete();'`
  3. Run `php artisan native:run` and wait up to 6 minutes.
  4. Run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='dump(App\Models\SourceRun::query()->latest("id")->take(6)->get(["source", "started_at", "finished_at", "error_kind"])->toArray(), App\Models\SentNotification::query()->get(["sent_at", "text"])->toArray());'`
  5. Quit the app. Make all sources due: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='App\Models\SourceRun::query()->each(fn ($r) => $r->update(["started_at" => $r->started_at->subDay()]));'`. Run `php artisan native:run`, wait up to 8 minutes, then run step 4 again.
- Expected: in step 3 a native notification appears, with the title "Sudo Hire" and a text like "6 new jobs: 4 Laravel, 2 PHP". Step 4 shows source runs from the last minutes and one sent notification with the same text. macOS can first ask to allow notifications for the app: allow them. If there are 0 new matches, no notification appears and step 4 shows no sent notification: set fewer filters and do steps 2 and 3 again. In step 5 the full fetch runs while the app starts the next `schedule:run`. Each new source run has a `finished_at` value, also when the scan took more than 60 seconds.
- Result: passed 2026-11-02, on a Mac, on branch m7/native-scheduler. Step 3: the scan at 07:05 UTC ran all six sources with no error and showed "Sudo Hire" with "4 new jobs" (no include keywords were set). Step 5: the full fetch at 07:10 took 3 seconds, and every source run has `finished_at`. A scan longer than 60 seconds was not seen.

## M7: A click on the notification opens the menu bar list

- Added: 2026-11-01, branch m7/native-scheduler
- Why manual: tests fake the internal API. The cloud container cannot open the menu bar or click a notification.
- Setup: as for "M7: The app scans on its own and shows a native notification", with the app still running.
- Steps:
  1. Do steps 2 and 3 of "M7: The app scans on its own and shows a native notification", so that a notification appears.
  2. Click the notification.
- Expected: the menu bar list opens under the menu bar icon. Before M8 the list can be empty or show the placeholder page.
- Result: passed 2026-11-02, on a Mac, on branch m7/native-scheduler. The menu bar list opened.

## M7: No notification with no settings stored

- Added: 2026-11-01, branch m7/native-scheduler
- Why manual: tests fake the scheduler and the internal API.
- Setup: as for "M7: The app scans on its own and shows a native notification", with the app not running.
- Steps:
  1. Remove the stored settings and make the sources due: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='App\Models\StoredSettings::query()->delete(); App\Models\Job::query()->update(["notified_at" => null]); App\Models\SentNotification::query()->delete(); App\Models\SourceRun::query()->each(fn ($r) => $r->update(["started_at" => $r->started_at->subDay()]));'`
  2. Run `php artisan native:run` and wait up to 6 minutes.
  3. Run step 4 of "M7: The app scans on its own and shows a native notification".
- Expected: no notification appears. Step 3 shows new source runs from the last minutes and no sent notification.
- Result: passed 2026-11-02, on a Mac, on branch m7/native-scheduler. The scan at 07:25 UTC ran all six sources with no error. No notification appeared and no notification was stored.

## M7: hire:scan in a terminal still uses osascript

- Added: 2026-11-01, branch m7/native-scheduler
- Why manual: the cloud container has no `osascript` and no macOS notifications.
- Setup: as for "M6c: Scan shows a macOS notification". The app does not need to run. The settings in `database/database.sqlite` must have a country.
- Steps:
  1. Do steps 1 and 2 of "M6c: Scan shows a macOS notification".
- Expected: the same result as in that check: "Notification sent: ..." and a macOS notification from Script Editor, not from the app. With no country in the settings, the command prints "No notification: set your country first." and no notification appears.
- Result: passed 2026-11-02, on a Mac, on branch m7/native-scheduler. "Notification sent: 6 new jobs", and the notification came from Script Editor. The line for no country was not checked on the Mac.

## M8a: The menu bar shows the unseen count after a scan

- Added: 2026-11-02, branch m8a/states-api
- Why manual: tests fake the internal API. The cloud container cannot see the macOS menu bar.
- Setup: run `php artisan migrate` and `php artisan native:migrate` (README, "Two databases"). The settings in `database/nativephp.sqlite` have a country (see step 2 of "M7: The app scans on its own and shows a native notification").
- Steps:
  1. Make the jobs unseen and the sources due: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='App\Models\Job::query()->update(["seen_at" => null, "state" => null, "state_at" => null]); App\Models\SourceRun::query()->each(fn ($r) => $r->update(["started_at" => $r->started_at->subDay()]));'`
  2. Run `npm run build`, then `php artisan native:run`. Do not open the list. Wait up to 6 minutes.
  3. Run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='dump(app(App\List\JobList::class)->unseenCount());'`
- Expected: after the first scan, a number shows next to the menu bar icon. It is the same as the number in step 3. With 0 new matches the label is empty: set fewer filters and do the steps again.
- Result: not run

## M8a: Closing the list marks the jobs as seen

- Added: 2026-11-02, branch m8a/states-api
- Why manual: tests send the menu bar events by hand. The cloud container cannot open or close the menu bar list.
- Setup: as for "M8a: The menu bar shows the unseen count after a scan", with the app running and a number next to the icon.
- Steps:
  1. Click the menu bar icon to open the list. Click outside the list to close it.
  2. Open the list again. While it is open, store one new job: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='app(App\Storage\JobStore::class)->save([new App\Schema\NormalisedJob(source: "larajobs", sourceJobId: "qa-m8a", sourceUrl: "https://larajobs.com/qa", applyUrl: "https://larajobs.com/qa", title: "QA Laravel Developer", company: "QA Company", description: null, tags: [], workMode: App\Schema\WorkMode::Remote, eligibility: App\Schema\Eligibility::worldwide(), officeLocation: null, timezoneMin: null, timezoneMax: null, salary: null, contractType: App\Schema\ContractType::FullTime, seniority: App\Schema\Seniority::Senior, postedAt: Carbon\CarbonImmutable::now(), fetchedAt: Carbon\CarbonImmutable::now(), expiresAt: null, attribution: "via LaraJobs")]);'`
  3. Close the list.
  4. Remove the test job: run `DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan tinker --execute='App\Models\Job::query()->where("source_job_id", "qa-m8a")->delete();'`
- Expected: after step 1 the label is empty (0 unseen). After step 3 the label shows 1: the job stored while the list was open stays unseen. The job must pass your filters: if the label stays empty, check your keywords and work modes.
- Result: not run

## M8a: The JSON API answers with php artisan serve

- Added: 2026-11-02, branch m8a/states-api
- Why manual: tests call the routes inside PHP. This check uses a real server, a real session and the local database.
- Setup: run `php artisan migrate` and `php artisan native:migrate` (README, "Two databases"). Run `php artisan serve` in a second terminal.
- Steps:
  1. Run `curl -s -H 'Accept: application/json' http://127.0.0.1:8000/api/list`
  2. Run `curl -s -H 'Accept: application/json' http://127.0.0.1:8000/api/sources`
  3. Run `curl -s -H 'Accept: application/json' http://127.0.0.1:8000/api/diagnostics`
  4. Run `curl -s -o /dev/null -w '%{http_code}\n' -X PUT -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"state":"saved"}' http://127.0.0.1:8000/api/jobs/1/state`
- Expected: step 1 gives JSON with `counts`, `new`, `locationNotStated`, `saved` and `applied`. Step 2 gives one entry for each of the six sources, with `lastRunAt` and `lastError`. Step 3 gives `appVersion`, `macosVersion` (your macOS version), `phpVersion` and `sources`, and none of your keywords, companies or jobs. Step 4 prints 419: a write with no CSRF token is refused.
- Result: not run
