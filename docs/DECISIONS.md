# Decisions

Location: `docs/DECISIONS.md` (committed).

One entry per decision, newest at the bottom, in date order. Keep each entry short. If a decision replaces an older one, say which one. Never delete an entry.

## Template

```
## YYYY-MM-DD: <short title>

- Milestone: Mx
- Decision: <what was decided>
- Reason: <why, in one or two sentences>
- Replaces: <date and title of the older entry, or "none">
```

For a new source, also add:

```
- Source terms: <attribution, rate limit, restrictions, link to terms>
- Checked on: YYYY-MM-DD
```

## Entries

## 2026-10-28: Open source app, Premium later

- Milestone: before M1
- Decision: v1 is a free, open source app with no server. A paid Premium source (curated companies and AI on a Laravel server) may come later.
- Reason: free job APIs fit personal use with attribution. A paid service built on them conflicts with some source terms.
- Replaces: none

## 2026-10-28: No AI in v1

- Milestone: before M1
- Decision: filters and parsers use rules only.
- Reason: most sources give structured data. AI is planned only for Premium, once per job, for cases rules cannot parse.
- Replaces: none

## 2026-10-28: ATS reader is the main source, boards re-ranked

- Milestone: before M1
- Decision: the main v1 source is an ATS reader (Greenhouse, Lever, Ashby, Personio) over a company list set by the user. Board sources in v1: LaraJobs, Jobicy, Remotive, Himalayas, We Work Remotely. RemoteOK, Hacker News and Working Nomads move to v1.1. Arbeitnow is dropped.
- Reason: a count on 2026-10-28 found few PHP or Laravel jobs on the boards (RemoteOK 1 of 99, Arbeitnow 0 of 300, Hacker News October 2 of 263). Company career pages hold most relevant jobs, and their public ATS APIs have no board terms to break.
- Replaces: none

## 2026-10-28: Unknown eligibility is grouped, not hidden

- Milestone: before M1
- Decision: jobs with unknown eligibility go into a collapsed "Location not stated" group by default. Strict and lenient stay as options. `cet` is not a region: timezone text fills the timezone fields.
- Reason: on 2026-10-28, 55 of 99 RemoteOK jobs had an empty location. Strict by default would hide most of a source without telling the user.
- Replaces: none

## 2026-10-28: Schema freezes after real fixtures

- Milestone: before M1
- Decision: M1 saves one real response per v1 source and writes a field mapping table here before the schema code. The schema freeze in `AGENTS.md` rule 10 starts when M1 is merged.
- Reason: the schema was designed before any payload was read. A mismatch found in M1 is cheap. The same mismatch found in M3 blocks a cloud session.
- Replaces: none

## 2026-10-28: Terminal engine before the menu bar

- Milestone: before M1
- Decision: M6b adds `php artisan hire:scan`, run from cron or launchd, with a summary notification through `osascript`. The NativePHP menu bar (M7 to M9) comes after.
- Reason: the terminal engine is useful sooner, and the menu bar parts need a Mac to verify anyway.
- Replaces: none

## 2026-10-28: Timezone offsets are decimal hours

- Milestone: M1
- Decision: `timezone_min` and `timezone_max` are decimal hours (`float`, `decimal(4,2)` in SQLite), from -12 to 14. Approved by the project owner before the schema freeze.
- Reason: Himalayas `timezoneRestrictions` has offsets like -9.5, 3.5, 5.75 and 12.75. Integers would lose them.
- Replaces: the `int` type in `docs/REQUIREMENTS.md` section 5 (updated)

## 2026-10-28: Jobs have an optional expiry date

- Milestone: M1
- Decision: add `expires_at` (datetime, nullable) to the job schema. It cannot be before `posted_at`. Himalayas `expiryDate`, We Work Remotely `expires_at` and Greenhouse `application_deadline` fill it.
- Reason: the Himalayas terms say to remove expired jobs. Retention after 30 days is not enough. Approved by the project owner before the schema freeze.
- Replaces: none

## 2026-10-28: Apply link is the link the source gives

- Milestone: M1
- Decision: `apply_url` is the apply link as the source gives it, unchanged. When a source has no separate apply link, `apply_url` is the source's own job page. Never take a link from the description text.
- Reason: LaraJobs, Jobicy, Remotive, Himalayas and We Work Remotely only link to their own page. Jobicy and Himalayas terms say to keep their link on every apply button. Jobicy gives the employer link only with a paid key.
- Replaces: none

## 2026-10-28: Personio links are built from slug and id

- Milestone: M1
- Decision: Personio XML has no link field. `source_url` and `apply_url` are both `https://<slug>.jobs.personio.de/job/<id>`.
- Reason: it is the company's own public job page, so rule 11 still holds. Check the link with a real job in M2.
- Replaces: none

## 2026-10-28: Values with no enum case map to existing cases

- Milestone: M1
- Decision: no new enum cases. Personio `intern` gives contract type `unknown`, `student` gives seniority `junior`, `experienced` gives `unknown`. Himalayas and Jobicy `Manager` or `Director` give `lead`. Lever commitment `None` gives `unknown`.
- Reason: keeps the schema small. Interns stay visible, and the seniority filter can still hide `junior`.
- Replaces: none

## 2026-10-28: Laravel queue table is `queue_jobs`

- Milestone: M1
- Decision: the Laravel queue uses the table `queue_jobs` (`config/queue.php`, `DB_QUEUE_TABLE`). The `jobs` table holds job posts.
- Reason: the Laravel skeleton creates a queue table named `jobs`, and docs/REQUIREMENTS.md section 9 uses `jobs` for job posts.
- Replaces: none

## 2026-10-28: Eligibility and salary are flat columns

- Milestone: M1
- Decision: the `jobs` table stores eligibility as `eligibility_type`, `eligibility_regions` (JSON) and `eligibility_countries` (JSON), and salary as `salary_min`, `salary_max`, `salary_currency`, `salary_period` and `salary_raw`. Unique key: `source` + `source_job_id`. `fingerprint` is indexed.
- Reason: the filters in section 8 can then run in SQL. The schema objects in PHP stay the same.
- Replaces: none

## 2026-10-28: Fingerprint is computed by NormalisedJob

- Milestone: M1
- Decision: `NormalisedJob` builds `fingerprint` as `sha1(company|title)`, both lowercase with spaces collapsed. Sources do not set it. M6 may make the normalisation stricter (e.g. drop "GmbH", "(m/w/d)").
- Reason: every source gets the same rule, and no source can forget it.
- Replaces: none

## 2026-10-28: Static analysis at PHPStan level 8

- Milestone: M1
- Decision: PHPStan with Larastan at level 8 on `app`, `database`, `routes` and `tests/Fakes`. Laravel's own `config` files are not analysed.
- Reason: level 8 checks nulls, which matter for the many nullable schema fields. Levels 9 and 10 mostly flag `mixed` from decoded JSON, which the parsers will narrow anyway.
- Replaces: none

## 2026-10-28: TypeScript 6, not 7

- Milestone: M1
- Decision: pin `typescript` to `^6`.
- Reason: `vue-tsc` 3 does not run with TypeScript 7 (the native port) yet. Move to 7 when `vue-tsc` supports it.
- Replaces: none

## 2026-10-28: No remote fonts

- Milestone: M1
- Decision: the frontend uses the system font. The Bunny Fonts setup from the Laravel skeleton is removed.
- Reason: all data stays on the Mac (section 13), and CI must build offline.
- Replaces: none

## 2026-10-28: Field mapping from real fixtures

- Milestone: M1
- Decision: the table below maps each source to the job schema. Fixtures are in `tests/Fixtures/<source>/`, fetched on 2026-10-28 and trimmed to 3 items.
- Reason: the schema freeze (AGENTS.md rule 10) starts when M1 is merged. Every source field now fits the schema with a rule.
- Replaces: none

### Rules for every source

- `source`: the source key. `attribution`: the source's attribution text. `fetched_at`: the time of the fetch. `fingerprint`: computed by `NormalisedJob`.
- `description`: HTML to plain text (decode entities, strip tags). Empty gives `null`.
- `tags`: lowercase, no duplicates (done by `NormalisedJob`).
- `eligibility`: structured fields first. Free text goes to the eligibility parser (M4). Country names go to ISO codes through the country table (M4).
- `salary`: structured fields first. Free text goes to the salary parser (M5). No amount gives `null`.
- `expires_at`: the closing date when the source gives one, else `null`.
- Dates are stored in UTC. Anything not stated gives `unknown` (enums) or `null`.
- The ATS reader is one source with key `ats`. Its `source_job_id` is `<ats>:<slug>:<id>`, so ids from two companies never clash. Its attribution is "via <Company> careers".
- Lever, Ashby and Personio responses have no usable company name. Each company list entry stores a display name: by default the slug in title case (`spotify` gives "Spotify"), editable in settings. This is storage (M2, M6), not the job schema.

### Greenhouse (`greenhouse/jobs.json`, slug `gitlab`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | `greenhouse:<slug>:<id>` |
| `absolute_url` | `source_url`, `apply_url` | same link for both, unchanged |
| `title` | `title` | as is |
| `company_name` | `company` | as is |
| `content` | `description` | HTML entity escaped: decode first |
| `content` (`div.pay-range`) | `salary` | salary parser on the pay range text. Only some US and Canada jobs have it |
| `location.name` | `work_mode`, `eligibility`, `office_location` | free text, parts split by "; ". "Remote, Poland" gives `remote` and country `PL` |
| `departments[].name` | `tags` | department names |
| `first_published` | `posted_at` | ISO with offset, convert to UTC |
| none | `contract_type`, `seniority` | `unknown`, or the title parser |

`application_deadline` gives `expires_at` when set (`null` in the fixture).

Not used: `internal_job_id`, `requisition_id`, `metadata`, `offices`, `data_compliance`, `updated_at`, `language`, AI fields.

### Lever (`lever/postings.json`, slug `spotify`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | `lever:<slug>:<id>` |
| `hostedUrl` | `source_url` | unchanged |
| `applyUrl` | `apply_url` | unchanged |
| `text` | `title` | as is |
| company list entry | `company` | the response has no company field. Use the name stored with the company list entry (see the ATS rule above) |
| `descriptionPlain`, `lists[]`, `additionalPlain` | `description` | joined in this order |
| `workplaceType` | `work_mode` | `remote`, `hybrid`, `onsite` or `on-site` |
| `country`, `lists` "Where You'll Be" | `eligibility` | `country` is ISO alpha-2. For remote jobs it gives `restricted` with that country, unless the text says otherwise |
| `categories.location`, `categories.allLocations` | `office_location` | for hybrid and on-site only |
| `salaryRange` {min, max, currency, interval} | `salary` | when present. Otherwise salary parser on `additionalPlain` |
| `categories.commitment` | `contract_type` | "Permanent" or "Full-time" gives `full_time`, "Part-time" `part_time`, "Contract" `contract`, "None" or other `unknown` |
| `categories.team`, `categories.department` | `tags` | |
| `createdAt` | `posted_at` | Unix milliseconds |
| none | `seniority` | title parser |

### Ashby (`ashby/job-board.json`, slug `ashby`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | `ashby:<slug>:<id>` |
| `jobUrl` | `source_url` | unchanged |
| `applyUrl` | `apply_url` | unchanged |
| `title` | `title` | as is |
| company list entry | `company` | as for Lever |
| `descriptionPlain` | `description` | as is |
| `workplaceType`, `isRemote` | `work_mode` | "Remote", "Hybrid", "OnSite". `isRemote` true with no type gives `remote` |
| `location`, `address.postalAddress.addressCountry`, `secondaryLocations[]` | `eligibility` | "European Union" gives region `eu`. Each country of the job and its secondary locations is added |
| `location` | `office_location` | for hybrid and on-site only |
| `compensation.summaryComponents[]` with `compensationType` "Salary" | `salary` | `minValue`, `maxValue`, `currencyCode`. `interval` "1 YEAR" gives `year`, "1 MONTH" `month`, "1 DAY" `day`, "1 HOUR" `hour`. `raw` is `scrapeableCompensationSalarySummary` |
| `employmentType` | `contract_type` | `FullTime`, `PartTime`, `Contract`. `Intern` and `Temporary` give `unknown` |
| `department`, `team` | `tags` | |
| `publishedAt` | `posted_at` | ISO |
| none | `seniority` | title parser |

The currency can differ from the location (an Australia job paid in JPY). Use `currencyCode`, never the country.

### Personio (`personio/positions.xml`, slug `ottonova`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | `personio:<slug>:<id>` |
| slug and `id` | `source_url`, `apply_url` | `https://<slug>.jobs.personio.de/job/<id>` (see the Personio decision above) |
| `name` | `title` | as is |
| company list entry | `company` | as for Lever. `subcompany` holds a legal name and an id ("... AG - 9698"), so it is not used |
| `jobDescriptions/jobDescription` | `description` | "name: value" pairs joined. Skip the one named "URL" |
| `office`, `additionalOffices/office` | `office_location`, `eligibility` | city names. No remote field: `work_mode` comes from the title and description parser, else `unknown` |
| `employmentType` | `contract_type` | with `schedule`: `permanent` + `full-time` gives `full_time`, `permanent` + `part-time` `part_time`, `freelance` or `temporary` `contract`, `intern` `unknown` |
| `seniority` | `seniority` | `entry-level` or `student` gives `junior`, `experienced` `unknown`, `senior` `senior`, `lead` `lead` |
| `keywords`, `department` | `tags` | `keywords` is a comma list |
| `createdAt` | `posted_at` | ISO |
| none | `salary` | `null` |

`positions-empty.xml` is a real response with 0 positions. An unknown slug returns 404 HTML, not XML.

### LaraJobs (`larajobs/feed.xml`)

| Source field | Schema field | Rule |
|---|---|---|
| `guid` | `source_job_id` | as is |
| `link` | `source_url`, `apply_url` | the LaraJobs page, unchanged |
| `title` | `title` | as is |
| `job:company` | `company` | as is |
| `description`, `content:encoded` | `description` | empty in the feed, so `null` |
| `job:location` | `work_mode`, `eligibility`, `office_location` | free text, e.g. "Remote/USA", "London, UK (Hybrid)". The title may also say "US Only" |
| `job:salary` | `salary` | salary parser, e.g. "$160,000.00 - $180,000.00". Empty gives `null` |
| `job:job_type` | `contract_type` | `FULL_TIME`, `PART_TIME`, `CONTRACT` |
| `job:tags` | `tags` | comma list |
| `pubDate` | `posted_at` | RFC 822 |
| none | `seniority` | title parser |

### Jobicy (`jobicy/remote-jobs.json`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | as string |
| `url` | `source_url`, `apply_url` | the Jobicy page, unchanged (Jobicy terms) |
| `jobTitle` | `title` | as is |
| `companyName` | `company` | as is |
| `jobDescription` | `description` | HTML to text |
| none | `work_mode` | `remote`: Jobicy lists remote jobs only |
| `jobGeo` | `eligibility` | free text: "USA", "Europe", "Anywhere" |
| `salaryMin`, `salaryMax`, `salaryCurrency`, `salaryPeriod` | `salary` | absent when unknown. `yearly` gives `year`, `monthly` `month`, `daily` `day`, `hourly` `hour` |
| `jobType[]` | `contract_type` | "Full-Time", "Part-Time", "Contract" |
| `jobLevel` | `seniority` | "Junior", "Midweight", "Senior", "Manager" or "Director" (`lead`), "Any" (`unknown`) |
| `jobIndustry[]` | `tags` | |
| `pubDate` | `posted_at` | ISO |

`tag=php` also returns non PHP jobs. The keyword filter (section 8) handles them.

### Remotive (`remotive/remote-jobs.json`)

| Source field | Schema field | Rule |
|---|---|---|
| `id` | `source_job_id` | as string |
| `url` | `source_url`, `apply_url` | the Remotive page, unchanged (Remotive terms) |
| `title` | `title` | as is |
| `company_name` | `company` | as is |
| `description` | `description` | HTML to text |
| none | `work_mode` | `remote` |
| `candidate_required_location` | `eligibility`, `timezone_min`, `timezone_max` | free text, e.g. "USA, Canada, USA timezones", "Worldwide" |
| `salary` | `salary` | free text: "$170k - $200k", "$50-$75 /hour". Empty gives `null` |
| `job_type` | `contract_type` | `full_time`, `part_time`, `contract` and `freelance` (`contract`) |
| `tags[]` | `tags` | |
| `publication_date` | `posted_at` | ISO with no timezone: read as UTC |
| none | `seniority` | title parser |

`limit` is ignored: the API returns all matches.

### Himalayas (`himalayas/jobs.json`, `himalayas/search.json`)

| Source field | Schema field | Rule |
|---|---|---|
| `guid` | `source_job_id`, `source_url` | the Himalayas job page, unchanged |
| `applicationLink` | `apply_url` | unchanged, with its UTM parameter (Himalayas terms) |
| `title` | `title` | as is |
| `companyName` | `company` | as is |
| `description` | `description` | HTML to text |
| none | `work_mode` | `remote` |
| `locationRestrictions[]` | `eligibility` | empty list gives `worldwide`. Otherwise `restricted` with country names mapped to ISO codes |
| `timezoneRestrictions[]` | `timezone_min`, `timezone_max` | min and max of the list. Values can be fractional. Empty gives `null` |
| `minSalary`, `maxSalary`, `currency`, `salaryPeriod` | `salary` | only when min or max is set. `annual` gives `year`, `monthly` `month`. `salaryPeriod` and `currency` can be set without amounts: then `null` |
| `employmentType` | `contract_type` | "Full Time", "Part Time", "Contractor" (`contract`) |
| `seniority[]` | `seniority` | the lowest level in the list. "Entry-level" or "Junior" gives `junior`, "Mid-level" `mid`, "Senior" `senior`, "Manager", "Director" or "Executive" `lead` |
| `categories[]`, `parentCategories[]` | `tags` | slugs, dashes become spaces |
| `pubDate` | `posted_at` | Unix seconds |
| `expiryDate` | `expires_at` | Unix seconds |

The Himalayas terms ask to remove expired jobs: the filters hide a job after `expires_at`. Search pages use `?page=`, the browse feed uses `nextCursor`.

### We Work Remotely (`weworkremotely/back-end.rss`)

| Source field | Schema field | Rule |
|---|---|---|
| `guid` | `source_job_id` | as is |
| `link` | `source_url`, `apply_url` | the WWR page, unchanged |
| `title` | `company`, `title` | "Company: Title", split at the first ": " |
| `description` | `description` | HTML entity escaped: decode first |
| none | `work_mode` | `remote` |
| `country`, `region` | `eligibility` | `country` is a comma list with flag emoji: strip the emoji, map names to ISO codes. It wins over `region`, which often says "Anywhere in the World" even for European jobs. Empty `country` and "Anywhere in the World" give `worldwide` |
| `type` | `contract_type` | "Full-Time", "Part-Time", "Contract" |
| `skills`, `category` | `tags` | `skills` is a comma list ending in "and X" |
| `pubDate` | `posted_at` | RFC 822 |
| `expires_at` | `expires_at` | RFC 822 |
| none | `salary`, `seniority` | `null` and the title parser |

Not used: `state` (company headquarters), `media:content`.
