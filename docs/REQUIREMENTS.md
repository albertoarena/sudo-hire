# Sudo Hire: requirements (v1)

Status: draft, revised 2026-10-29 (Personio link and Himalayas search checked). Location in the repository: `docs/REQUIREMENTS.md`.

## 1. Purpose

Sudo Hire is an open source macOS menu bar app for developers. It fetches new job posts from public job boards, keeps only the jobs that match the user's filters, and sends one summary notification every N hours.

The main problem it solves: "remote" often does not mean "remote from anywhere". A developer in Italy must not see a job that is "Remote, US only".

## 2. Scope

### In scope for v1

- Fetch jobs from company ATS career pages and public job board APIs and feeds (section 4).
- Normalise all jobs into one schema (section 5).
- Parse eligibility, salary, contract type and seniority with rules, no AI.
- Filter, deduplicate and store jobs locally in SQLite.
- Summary notification every N hours.
- Menu bar UI with the list of new matches and a settings screen.
- Signed and notarised DMG on GitHub releases.

### Out of scope for v1

- Server, accounts, payments, telemetry.
- AI of any kind.
- LinkedIn, Indeed, Wellfound, Upwork scraping. These have no usable public API, or their terms forbid it.
- Sources that need an API key (Adzuna, Jooble, Upwork). Planned for v1.x, with the user's own key.
- Premium source (section 12). v1 only keeps the design ready for it.

## 3. Platform and stack

- macOS, menu bar app.
- Laravel, NativePHP `nativephp/desktop` 2.x, PHP 8.4, SQLite.
- Vue 3 with TypeScript, Tailwind CSS.
- Pest, Vitest, PHPStan.
- License: MIT (to confirm).

## 4. Sources

Each source is one class implementing `JobSource`. At implementation time, each source milestone must:

1. Check the current endpoint and terms of use.
2. Record the terms (attribution, rate limit, restrictions) in `docs/DECISIONS.md`.
3. Add trimmed real responses as fixtures.

Public job boards carry few PHP and Laravel jobs. A count on 2026-10-28: RemoteOK 1 of 99, Arbeitnow 0 of 300, Jobicy 17 (`tag=php`), Remotive 11, LaraJobs 5, Hacker News "Who is hiring" (October) 2 of 263. Most relevant jobs are on company career pages. So the main v1 source is an ATS reader, and the boards come second.

### ATS reader (main source)

One source class that reads a list of companies set by the user. Each entry is `<ats>:<slug>`, e.g. `greenhouse:acme`.

| ATS | Endpoint | Format | Status 2026-10-28 |
|---|---|---|---|
| Greenhouse | `https://boards-api.greenhouse.io/v1/boards/<slug>/jobs` | JSON | 200 |
| Lever | `https://api.lever.co/v0/postings/<slug>?mode=json` | JSON | 200 |
| Ashby | `https://api.ashbyhq.com/posting-api/job-board/<slug>` | JSON | 200 |
| Personio | `https://<slug>.jobs.personio.de/xml` | XML | 200, shape checked with a real company in M1. No link field: the built job link was checked on 2026-10-29 (see `docs/DECISIONS.md`) |

- Min interval: 6 hours per company.
- Attribution: "via <Company> careers". `source_url` and `apply_url` are the company's own links.
- Settings: add, remove and list companies. Show the last result per company (jobs found, or the error, e.g. unknown slug).
- A bundled starter list is optional. Laravel Partners (laravel.com/partners) is a good seed. Each entry must be checked by hand before it ships.
- Premium (section 12) is a curated version of this list. The reader code stays in the open source app.

### Job boards

| Source | Format | Min interval (default) | Notes |
|---|---|---|---|
| LaraJobs | RSS, `https://larajobs.com/feed` | 2 hours | Laravel only. Has `job:company`, `job:location`, `job:salary`, `job:job_type`, `job:tags`. |
| Jobicy | JSON API | 1 hour | Use `tag=php`. Max 50 per request. Credit and link back. |
| Remotive | JSON API | 6 hours | Has `search`. Max 4 fetches per day, 24 hour delay by design, attribution required. |
| Himalayas | JSON API, paginated | 2 hours | Country list and timezone restrictions available. Search endpoint exists, PHP volume to check. Credit and link back, keep apply link. |
| We Work Remotely | RSS, back-end category feed | 2 hours | Region often in title or category. |

### Later (v1.1 or not planned)

| Source | Decision | Reason |
|---|---|---|
| RemoteOK | v1.1 | 1 of 99 PHP, 55 of 99 with an empty location. Terms: followed link back, name "Remote OK", no logo. |
| Hacker News "Who is hiring" (Algolia API) | v1.1 | 2 PHP posts a month, free text. |
| Working Nomads | v1.1 | 3 of 42 PHP. |
| Arbeitnow | Dropped | 0 of 300 PHP, mostly on-site in Germany. |
| GermanTechJobs, DevITjobs, SwissDevJobs | Not planned | JSON works but is undocumented, terms unknown, mostly on-site. |

The user can enable or disable each source in settings.

## 5. Job schema

One normalised job. Changes need approval (see `AGENTS.md`).

| Field | Type | Notes |
|---|---|---|
| `source` | string | Source key, e.g. `remoteok` |
| `source_job_id` | string | ID at the source |
| `source_url` | string | Job page at the source (link back) |
| `apply_url` | string | Original apply link, never replaced |
| `title` | string | |
| `company` | string | |
| `description` | text, nullable | Plain text |
| `tags` | string[] | Lowercase |
| `work_mode` | enum | `remote`, `hybrid`, `onsite`, `unknown` |
| `eligibility` | object | See section 6 |
| `office_location` | string, nullable | Free text, for hybrid and on-site |
| `timezone_min`, `timezone_max` | decimal, nullable | UTC offsets in hours, e.g. -9.5 or 5.75 |
| `salary` | object, nullable | See section 7 |
| `contract_type` | enum | `full_time`, `part_time`, `contract`, `unknown` |
| `seniority` | enum | `junior`, `mid`, `senior`, `lead`, `unknown` |
| `posted_at` | datetime | |
| `fetched_at` | datetime | |
| `expires_at` | datetime, nullable | When the source says the job closes. Hide the job after this date |
| `attribution` | string | e.g. "via RemoteOK" |
| `fingerprint` | string | For deduplication (section 9) |

## 6. Eligibility

The app answers one question: can the user do this job from where they are?

### Model

```
eligibility:
  type: worldwide | restricted | unknown
  regions: [europe, eu, emea, north_america, latam, apac, ...]
  countries: [IT, DE, ...]   # ISO 3166-1 alpha-2
```

### Rules

- The user sets one country in settings (e.g. `IT`).
- A lookup table maps each country to its regions. Example: `IT` belongs to `eu`, `europe`, `emea`.
- Timezones are not regions. A text like "CET" or "UTC+1 ±2" fills `timezone_min` and `timezone_max`, and the timezone filter (section 8) handles it.
- A job passes if:
  - `type` is `worldwide`, or
  - its `countries` contain the user's country, or
  - its `regions` contain any region of the user's country.
- `unknown` follows the setting:
  - **group** (default): shown in a separate, collapsed "Location not stated" group, not counted in the notification.
  - **strict**: hidden.
  - **lenient**: shown with the matches.
- Reason for the default: on 2026-10-28, 55 of 99 RemoteOK jobs had an empty location and 10 said only "Remote". Strict would hide most of a source without telling the user.

### Parsing

1. Use structured fields when the source has them (country lists, region fields).
2. Otherwise parse free text with rules: "US only", "USA", "North America", "UK only", "EU", "Europe", "EMEA", "anywhere", "worldwide", and similar. Timezone text goes to the timezone fields.
3. Parser tests use a fixture table: real location strings with the expected result. Add a row for every bug found.

## 7. Salary

### Model

```
salary:
  min: number, nullable
  max: number, nullable
  currency: EUR | USD | GBP | other ISO 4217
  period: year | month | day | hour | unknown
  raw: string   # original text
```

### Rules

- Two separate filters:
  - **Min annual salary** for employee jobs. Monthly values are multiplied by the number of monthly payments: 12, 13 or 14 (setting, default 12). Italian offers often quote 13 or 14.
  - **Min day rate** for contract jobs. Hourly values are multiplied by 8 (configurable).
- The contract type decides which filter applies. A job with unknown contract type but an hourly or daily rate counts as a contract.
- **Ranges:** a job passes if the top of the range (`max`, or `min` if no `max`) reaches the user's minimum.
- **Missing salary:** follows the strict or lenient setting. Default: **lenient** (shown with a "?" badge).

### Currency conversion

- Display currency: EUR, USD or GBP (setting).
- The app shows the original value and the converted value with "≈", e.g. `$120k - $150k / year (≈ €103k - €129k)`. If the currencies are the same, show one value.
- Filters compare converted values.
- Rates: ECB daily reference rates (directly or through Frankfurter). Fetch once per day, cache in SQLite. If the fetch fails, use the last cached rates. Show the rate date in settings.

### Parsing

Structured fields first. Free text with rules, e.g. "60-70k EUR DOE", "£450 p/d", "$60/hour". Fixture table tests, like eligibility.

## 8. Filters

| Filter | Values | Default |
|---|---|---|
| Work mode | remote, hybrid, onsite (multi) | remote |
| Work from (country) | ISO country | set on first run |
| Unknown eligibility | group / strict / lenient | group |
| Timezone overlap | e.g. ±3 hours from user | off |
| Keywords include | list | empty |
| Keywords exclude | list | empty |
| Min annual salary | amount in display currency | off |
| Min day rate | amount in display currency | off |
| Unknown salary | strict / lenient | lenient |
| Contract type | full_time, part_time, contract (multi) | all |
| Seniority | junior, mid, senior, lead (multi) | all |
| Posted within | 24h, 3 days, 7 days | 3 days |
| Company blocklist | list | empty |
| Sources | toggle per source | all on |

Keywords match title and tags, case-insensitive, whole words. Description matching is a setting, default off.

Office location (city and radius) for hybrid and on-site jobs is **v2**.

## 9. Deduplication and storage

- Fingerprint: normalised company + normalised title. If two sources have the same fingerprint within 14 days, keep one job and store the other sources as "also on".
- Store seen job IDs, so a job is never notified twice.
- Retention: delete jobs older than 30 days.
- Tables (indicative): `jobs`, `job_sources`, `source_runs`, `exchange_rates`, `settings`, `ats_companies`.
- `source_runs` stores per run: started and finished time, last error, items fetched, items skipped (could not normalise), and the share with unknown eligibility and unknown salary. Parsers are tuned from these numbers, not from fixtures alone.

## 10. Scheduling and notifications

- A scheduler checks every few minutes which sources are due, based on each source's minimum interval.
- **Notification interval:** every N hours, N from 1 to 24 (setting). Default: 2.
- One summary notification per interval, e.g. "6 new jobs: 4 Laravel, 2 PHP". Grouping by the first matching include keyword.
- No notification if there are 0 new matches.
- Clicking the notification opens the menu bar list.

## 11. UI (to be detailed later)

Minimum for v1:

- Menu bar icon with a badge for unseen matches.
- List of new matches: title, company, salary (original and converted), eligibility, source attribution, posted date. Apply opens the original apply link in the browser.
- Settings screen for all filters, intervals, sources, display currency.
- Source status: last success and last error per source.
- Clear message if the internal PHP server stopped (see `AGENTS.md`, NativePHP notes).

Detailed UI design will be added in a later revision.

## 12. Premium readiness (not built in v1)

A later paid Premium source will run on a separate Laravel server (sudohire.dev). It will provide:

- A curated list of EU remote-friendly Laravel and PHP companies, as `<ats>:<slug>` entries for the open source ATS reader (section 4). This is data, not code. It is built on company career pages, so it does not depend on the job boards' terms.
- AI classification of jobs that rules cannot parse (unknown eligibility, unclear salary). Done once per job on the server, not per user.

v1 must keep these hooks:

- `JobSource` works for a remote source with a license key.
- The ATS reader can take its company list from a remote list as well as from settings.
- The job schema is the same for Premium jobs.
- Settings have room for a license key field (hidden in v1).

## 13. Privacy

- All data stays on the Mac. No telemetry, no accounts.
- API keys (v1.x) are stored locally and only sent to their own source.

## 14. Testing

- TDD for every feature (see `AGENTS.md`).
- Sources: fixture-based tests with `Http::fake()`. No live network in CI.
- Parsers: fixture tables of real strings.
- Filters: one test per filter, plus group, strict and lenient cases.
- Notifications and menu bar: tests with fakes, plus manual steps in `docs/QA.md`.

## 15. Release

Tag, CI builds, signs and notarises a **draft** GitHub release. A human installs and tests the draft, then publishes.

## 16. Suggested milestones

| # | Milestone | Where |
|---|---|---|
| M1 | Scaffold, real fixtures for every v1 source, field mapping table, job schema, `JobSource` interface, CI | Local |
| M2 | Sources: ATS reader (Greenhouse, Lever, Ashby, Personio), LaraJobs, Jobicy | Cloud |
| M3 | Sources: Remotive, Himalayas, We Work Remotely | Cloud |
| M4 | Eligibility parser and country/region table | Cloud |
| M4b | Title parser: seniority and contract type from the job title | Cloud |
| M5 | Salary parser, exchange rates, conversion | Cloud |
| M6a | Storage, source registry and runner, parser wiring, deduplication, retention, `source_runs` stats | Cloud |
| M6b | Filters (section 8), as pure classes over the job and the settings | Cloud |
| M6c | `php artisan hire:scan`: fetch due sources, filter, print matches, one summary notification through `osascript`. Runs from cron or launchd, no NativePHP needed | Cloud + Mac check |
| M7 | NativePHP scheduler wiring and native notifications, reusing M6b | Mac or cloud + Mac check |
| UI design | Screens, states and design tokens for the menu bar list and settings, with Impeccable. Updates section 11 | Local |
| M8 | Menu bar UI and settings | Mac or cloud + Mac check |
| M9 | Signing, notarisation, release flow | Mac |

M4, M4b and M5 can run in parallel with M2 and M3. A local prep pull request comes first.

M6a and M6b run in parallel, after a local prep pull request that adds the shared `Settings` value object. UI design runs locally in parallel with M6 and must be merged before the M8 brief.

## 17. Open questions

- License: MIT?
- UI design for the menu bar list and settings.
- Spatie Event Sourcing is not planned for v1 (local app, simple state). Reconsider for the Premium server.
