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
