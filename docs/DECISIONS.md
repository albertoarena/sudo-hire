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

## 2026-10-29: Personio job link checked

- Milestone: prep for M2
- Decision: the built link `https://<slug>.jobs.personio.de/job/<id>` is correct. The fixture job 2807356 at `ottonova` returned 200 with the job title in the page. An unknown id returned 404.
- Reason: closes the open question from the entry "Personio links are built from slug and id".
- Replaces: none
- Checked on: 2026-10-29

## 2026-10-29: Himalayas search has enough PHP jobs

- Milestone: prep for M3
- Decision: M3 uses the Himalayas search endpoint with `q=php` and `q=laravel`. On 2026-10-29, `q=php` had a total count of 1452 and `q=laravel` 361. On the first page, 16 of 20 (`php`) and 18 of 20 (`laravel`) jobs had PHP or Laravel in the title or categories.
- Reason: closes the open question in `docs/REQUIREMENTS.md` section 17. The total count includes matches in the description, so the first page is the better measure. Search pages use `?page=`, 20 jobs to a page.
- Replaces: none
- Checked on: 2026-10-29

## 2026-10-29: Shared helpers and fixtures before the cloud sessions

- Milestone: prep for M2 to M5
- Decision: a local pull request adds the HTML to plain text helper (`App\Sources\Support\HtmlToText`), one real ECB daily rates file, and real job titles for the M4b title parser (six trimmed files). An email address in the Cloudflare file is replaced with "[email removed]". The cloud milestones use them and do not edit `app/Sources/Support/`.
- Reason: M2 and M3 run in parallel and both need the helper. The ECB file and the extra titles need network access that a cloud session may not have.
- Replaces: none

## 2026-10-29: Title parser is a separate milestone

- Milestone: prep for M4b
- Decision: a new milestone M4b gives seniority and contract type from the job title. It runs in parallel with M2 to M5. M6 connects it to the sources, and a structured source field wins over the title.
- Reason: no milestone owned the "title parser" rows of the field mapping, and M6 is already the largest milestone.
- Replaces: none

## 2026-10-29: Cloud sessions use one commit approval before the push

- Milestone: prep for M2 to M5
- Decision: a cloud session commits freely on its branch. Before the first push, it shows all commit messages and the pull request text for one approval. Each later push needs a yes for its new messages. Local sessions keep the approval for each commit.
- Reason: with five cloud sessions in parallel, an approval for each commit stops the work most of the time. The human still approves all text before it leaves the session.
- Replaces: none

## 2026-10-29: Dependencies install on PHP 8.3

- Milestone: prep for M2 to M5
- Decision: `composer.json` requires PHP `^8.3` and pins `config.platform.php` to 8.3.0. The lock now has Symfony 7.4, Pest 4 and PHPUnit 12. Laravel 13 and NativePHP 2.4 are unchanged. CI runs Pest and PHPStan on PHP 8.3 and 8.4. The app still bundles PHP 8.4.
- Reason: the cloud container has PHP 8.3 and cannot install 8.4. With the old lock (Symfony 8, Pest 5, PHPUnit 13), no test could run in a cloud session. Test first must work there too.
- Replaces: "PHP 8.4 as the minimum in `composer.json`" from the M1 brief

## 2026-10-29: Cloud setup script does not depend on the repository

- Milestone: prep for M2 to M5
- Decision: `scripts/cloud-setup.sh` turns off AI attribution in `~/.claude/settings.json` and commit signing, and sets the git identity from the `GIT_*` variables. It does not install packages. The environment holds the `GIT_*` variables and `COMPOSER_ALLOW_SUPERUSER=1`.
- Reason: the setup script runs before the repository is cloned. The M1 script exited at once outside the repository, so it did nothing in a real cloud session. A network secret does not help Composer: GitHub refuses package downloads that the session's repository access does not cover.
- Replaces: the M1 version of `scripts/cloud-setup.sh`


## 2026-10-29: ATS reader design

- Milestone: M2
- Decision: the `ats` source reads its companies through the `CompanyList` interface. `DatabaseCompanyList` (the `ats_companies` table) is the default binding. A remote list (Premium) can implement the same interface. One reader class for each ATS is behind the one source. Each raw item is an `AtsItem` with its company entry and the fetch time.
- Decision: a company is skipped for 6 hours after each read, also after a failed read. A failing company stores its error (for example "HTTP 404") and the reader continues with the next company.
- Decision: a slug can only have letters, digits, dots, dashes and underscores. The Personio slug becomes part of a host name, so other characters could change the link.
- Decision: the commands are `ats:add <ats>:<slug> [--name=]`, `ats:remove <ats>:<slug>` and `ats:list`. The display name is stored in the table and can be changed there. There is no rename command yet.
- Reason: the brief asks for a list that a remote list can replace, and for a reader that a failing company cannot stop. Never read a company more often than the interval, also after an error.
- Replaces: none

## 2026-10-29: Contract test checks that each job has an attribution

- Milestone: M2
- Decision: `JobSourceContractTest` checks that each job has a non-empty attribution. It no longer checks that the job attribution is the same as `JobSource::attribution()`. It still checks that the job source is the source key. The `JobSource` interface is unchanged.
- Reason: ATS jobs use "via <Company> careers" (REQUIREMENTS section 4), so each company gives a different text. `AtsSource::attribution()` gives "via company careers" for the source as a whole. Approved by the project owner in the M2 session.
- Replaces: none

## 2026-10-29: ATS endpoint parameters

- Milestone: M2
- Decision: Greenhouse uses `?content=true` and Ashby uses `?includeCompensation=true` on the endpoints of REQUIREMENTS section 4. Lever uses `?mode=json`. Personio uses `/xml`. Jobicy uses `?count=50&tag=php`.
- Reason: without these parameters, Greenhouse gives no `content` and Ashby gives no `compensation`. The fixtures have both fields, and the field mapping uses them.
- Replaces: none

## 2026-10-29: Source fields that wait for a parser

- Milestone: M2
- Decision: until the parsers exist (M4, M4b, M5), these fields stay `unknown` or `null`: Greenhouse location, LaraJobs location and salary, Jobicy `jobGeo`, Ashby locations (country names), Personio work mode, Lever salary text, and the seniority from the title for all sources except Personio and Jobicy.
- Decision: Lever `country` gives a restricted eligibility only for a remote job, as in the field mapping. A hybrid or on-site Lever job gives `unknown` eligibility and its office locations.
- Decision: when Lever `descriptionPlain` is empty, the reader uses the HTML `description` as plain text. Two of three fixture jobs have an empty `descriptionPlain`.
- Decision: a Personio description is the "name: value" pairs, joined by an empty line. The part named "URL" is skipped.
- Decision: Lever `salaryRange` and the Jobicy salary fields have no original text. The reader builds `raw` from the fields, e.g. "100000 - 125000 USD yearly". A salary with a bad currency gives no salary. The job is not skipped.
- Decision: the Lever `salaryRange` intervals (`per-year-salary`, `per-month-salary`, `per-day-wage`, `per-hour-wage`) come from the Lever API documentation as it was known. No fixture has a `salaryRange`. Other intervals give `unknown`.
- Reason: the brief says to use only structured fields in M2. A wrong value is worse than `unknown`, because `unknown` goes to the "Location not stated" group.
- Replaces: none

## 2026-10-29: Source terms of the ATS reader

- Milestone: M2
- Decision: the ATS reader uses the public job board APIs of Greenhouse, Lever, Ashby and Personio. Each job keeps the company's own job link and apply link. The attribution is "via <Company> careers".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms.
- Source terms: Greenhouse says that "Job Board data is publicly available": GET endpoints need no authentication. Lever says published postings are public and "may be scraped by third parties". Ashby serves the published postings for career pages. Personio publishes no terms for the XML feed, which is the company's own public feed. None states an attribution or a rate limit. The app keeps each company's links and reads each company at most every 6 hours.
- Checked on: 2026-10-29

## 2026-10-29: Source terms of LaraJobs

- Milestone: M2
- Decision: the LaraJobs source reads `https://larajobs.com/feed` every 2 hours. Source and apply links are the LaraJobs job page. The attribution is "via LaraJobs".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms.
- Source terms: LaraJobs publishes no terms page (`/terms` returns 404) and no rules for the public feed. The project owner accepts the feed as it is. Each job links to its LaraJobs page, which forwards to the employer's apply page. The app shows "via LaraJobs" and reads the feed at most every 2 hours.
- Checked on: 2026-10-29

## 2026-10-29: Source terms of Jobicy

- Milestone: M2
- Decision: the Jobicy source reads `https://jobicy.com/api/v2/remote-jobs?count=50&tag=php` every hour. Source and apply links are the Jobicy job page. The attribution is "via Jobicy".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms. The fixture response of 2026-10-28 says: "Please ensure Jobicy is clearly credited with a direct link to the source, and all application buttons redirect to the original job URL provided in this feed." The source follows this text.
- Source terms: apply links "must continue to direct users to the original job pages on Jobicy". Credit Jobicy and do not present its listings as your own. "Start a new automated pass no more than once per hour." Commercial use is allowed. The source follows all of these.
- Checked on: 2026-10-29

## 2026-10-29: A workflow removes the attribution footer

- Milestone: after M2
- Decision: the workflow `strip-attribution.yml` runs when a pull request is opened, edited or reopened. It removes a "Generated by" or "Generated with" Claude Code footer from the body. The rule is in `scripts/strip-attribution.mjs`, with Vitest tests.
- Reason: the platform adds the footer to pull requests from cloud sessions, also when the session sends a body without it. Attribution settings in the container do not stop it. Hard rule 6 forbids the footer.
- Replaces: none

## 2026-10-29: A script puts PHPStan into the Composer cache

- Milestone: after M2
- Decision: `scripts/phpstan-cache.sh` clones the locked PHPStan tag (one commit) and zips it under the name Composer looks for in its cache. Cloud sessions run it when `composer install` fails on `phpstan/phpstan`. Tests run it against a local git repository, without network.
- Reason: cloud sessions spent about 10 minutes to rebuild this zip by hand. A `vcs` repository for PHPStan is no option: Composer mirrors its full history, which holds every released phar (more than 5 GB).
- Replaces: none

## 2026-10-29: M3 endpoints and paging

- Milestone: M3
- Decision: Remotive reads `https://remotive.com/api/remote-jobs?search=php`, one request for each fetch, every 6 hours. `search=laravel` is not read: it would double the requests.
- Decision: Himalayas reads only the search endpoint, `https://himalayas.app/jobs/api/search?q=php&page=N` and the same with `q=laravel`. The browse feed (`nextCursor`) is not used: it is not filtered. A job found by both searches is read once (same `guid`).
- Decision: each Himalayas search stops at a page with fewer than 20 jobs, at a page with no job newer than `$since`, or after 5 pages. The jobs of the last page are still read. Search is sorted by relevance, not by date, so `$since` cannot stop inside a page.
- Decision: We Work Remotely reads `https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss` every 2 hours.
- Reason: the project owner chose these options in the M3 session. Remotive advises at most 4 requests a day. `q=php` has about 73 pages, so a first fetch without a limit would send about 90 requests.
- Replaces: none

## 2026-10-29: M3 source fields that wait for a parser

- Milestone: M3
- Decision: until the parsers exist (M4, M4b, M5), these fields stay `unknown` or `null`: Remotive `candidate_required_location` and `salary`, a Himalayas `locationRestrictions` list that is not empty, the We Work Remotely `country` list and every region other than "Anywhere in the World", and the seniority from the title for Remotive and We Work Remotely.
- Decision: Himalayas salary has no original text. The source builds `raw` from the fields, e.g. "5500 - 7000 EUR monthly", as for Jobicy. A `salaryPeriod` other than `annual` or `monthly` gives `unknown`. A Himalayas `seniority` value that is not in the mapping is ignored.
- Decision: a Himalayas job with no `applicationLink` uses its `guid` page as the apply link (see "Apply link is the link the source gives"). A timezone offset outside -12 to 14, or an expiry date before the posted date, makes the job fail the schema, so it is skipped.
- Decision: We Work Remotely `type` and Himalayas `employmentType` give the contract type. These are fixed values from the field mapping, so they need no parser.
- Reason: the brief says to use only structured fields in M3. A wrong value is worse than `unknown`.
- Replaces: none

## 2026-10-29: Source terms of Remotive

- Milestone: M3
- Decision: the Remotive source reads `https://remotive.com/api/remote-jobs?search=php` every 6 hours. Source and apply links are the Remotive job page. The attribution is "via Remotive".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms. The fixture response of 2026-10-28 has a legal notice: "Please link back to the URL found on Remotive AND mention Remotive as a source". It also says: "we advise max. 4 times a day". It forbids sending the jobs to third party job sites, and it forbids showing them to collect sign-ups or email addresses. The source follows this text. The app shows jobs only to its own user.
- Source terms: the API README asks to "link back to the URL found on Remotive AND mention Remotive as a source". It advises at most 4 fetches a day and blocks more than 2 requests a minute. It forbids sending the jobs to other job sites and collecting sign-ups with them. Listings are delayed by 24 hours. The source follows all of these.
- Checked on: 2026-10-29

## 2026-10-29: Source terms of Himalayas

- Milestone: M3
- Decision: the Himalayas source reads the search endpoint every 2 hours, at most 5 pages for each search. The source link is the Himalayas job page (`guid`). The apply link is `applicationLink`, unchanged, with its `utm_source=api` parameter. The attribution is "via Himalayas". `expiryDate` gives `expires_at`.
- Reason: REQUIREMENTS section 4 and the M1 entries say: credit Himalayas, link back, keep the apply link, and remove expired jobs. The source follows these rules. The expiry filter is in M6.
- Source terms: the API terms ask to name Himalayas as the source with a visible link, to use `applicationLink` unchanged for every Apply action, and to remove expired jobs. Jobs may be shown "only on sites and apps you operate" and not passed on to other boards. The project owner accepts that each user runs the app on their own Mac, so it is an app the user operates. Without an API key, requests share a public allowance. The source reads at most 10 pages every 2 hours.
- Checked on: 2026-10-29

## 2026-10-29: Source terms of We Work Remotely

- Milestone: M3
- Decision: the We Work Remotely source reads the back-end category RSS feed every 2 hours. Source and apply links are the We Work Remotely job page. The attribution is "via We Work Remotely".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms. The feed has `<ttl>60</ttl>`, so a 2 hour interval is within its own refresh time.
- Source terms: the terms and conditions (updated 2026-10-23) have no rule about the RSS feed, automated access or reuse of listings. They give a limited, revocable right to access and use the material. The app reads the public feed every 2 hours, keeps the We Work Remotely links and shows "via We Work Remotely".
- Checked on: 2026-10-29

## 2026-10-29: Country and region tables

- Milestone: M4
- Decision: `App\Parsers\Location\Countries` maps names and common spellings to all 249 ISO 3166-1 alpha-2 codes. It is a plain PHP array. It does not use the intl extension, because the PHP that NativePHP bundles may not have it. Lookup ignores case, flag emoji, dots, extra spaces and a leading "the". "&" reads as "and".
- Decision: short codes are not read as countries, except the aliases "US", "USA", "UK" and "UAE". "CA" can mean California, so it gives nothing.
- Decision: `App\Parsers\Location\Regions` has these region keys: `africa`, `americas`, `apac`, `asia`, `emea`, `eu`, `europe`, `latam`, `middle_east`, `north_america`, `oceania`.
- Decision: each country gets its regions from its UN M49 sub-region. Africa gives `africa` and `emea`. Europe gives `europe` and `emea`. Northern America (US, CA, BM, GL, PM) gives `north_america` and `americas`. The Caribbean, Central and South America give `latam` and `americas`. Eastern, South-eastern and Southern Asia give `asia` and `apac`. Western Asia gives `asia` and `emea`. Central Asia gives `asia` only. Oceania gives `oceania` and `apac`. Antarctica gives no region.
- Decision: the 27 EU member states (europa.eu) also get `eu` and `europe`. The Middle East as job posts use it (AE, BH, EG, IL, IQ, IR, JO, KW, LB, OM, PS, QA, SA, SY, TR, YE) gets `middle_east` and `emea`, and never `apac`. Mexico is in `latam`, not in `north_america`, as in M49.
- Reason: REQUIREMENTS section 6 needs a table from each country to its regions. M49 and the EU list are public standards, so the table does not depend on one job board.
- Replaces: none

## 2026-10-29: Location parser rules

- Milestone: M4
- Decision: `LocationParser::parse()` reads one location text into eligibility, work mode, timezone min and max, and office location. Greenhouse parts split by ";" are read one by one, then merged.
- Decision: work mode words: "remote", "distributed", "work from home" give `remote`. "hybrid" gives `hybrid`. "on-site", "in-office", "office-based" give `onsite`. "anywhere" and "worldwide" also give `remote`. When parts disagree, the most remote mode wins, and only the parts with that mode give the eligibility.
- Decision: hybrid and on-site jobs give `unknown` eligibility and keep the rest of the text as the office location. This is the same rule as for Lever in "Source fields that wait for a parser".
- Decision: a place name gives `restricted`. Places win over worldwide words: "Anywhere in Europe" gives region `europe`. Words like "only", "based in" and "residents" around a place are ignored. A part that is not a place next to a country, e.g. the city in "Bangalore, India", is ignored. Text with no place, e.g. "New York, NY" or "Remote", gives `unknown`.
- Decision: text that excludes places ("except", "excluding", "not", "outside", "no") gives `unknown`. The parser does not read exclusions.
- Decision: "Georgia" in free text gives nothing, because it is also a US state ("Atlanta, Georgia"). A country list (Himalayas, We Work Remotely) still reads it as `GE`.
- Decision: timezone text fills the timezone fields and is removed before places are read, so it never gives a region. It reads "UTC+1", "GMT-3:30", "±2" spreads, zone ids such as "Europe/Berlin", abbreviations such as "CET" or "EST", names such as "Eastern Time", and "<country> timezones". An abbreviation or a zone gives the offsets of the whole year 2026, with summer time: "CET" gives 1 to 2. Other "<word> time zones" text, e.g. "European time zones", is removed with no offsets. Offsets stay inside -12 to 14.
- Decision: `countryList()` and `countryText()` read a list of country or region names (Himalayas `locationRestrictions`, We Work Remotely `country`). One name that is not in the tables gives `unknown`. An empty list gives `unknown`, so the source applies its own rule (both sources say empty is `worldwide`).
- Decision: `UnknownEligibilityShare::of()` gives the number, the total and the share of jobs with `unknown` eligibility. M6 stores it in `source_runs`.
- Reason: REQUIREMENTS section 6 says text the rules cannot read gives `unknown`, never a guess. `unknown` goes to the "Location not stated" group, so the user still sees the job.
- Replaces: none

## 2026-10-29: Timezone-only text is tested without a fixture

- Milestone: M4
- Decision: the parser tests for timezone-only text ("CET", "UTC+1 ±2", "USA timezones") are plain unit tests, marked "not from a fixture". All other rows of the fixture table come from a file in `tests/Fixtures/`, and a test checks that the string is in that file.
- Reason: no source gives timezone-only text in a location field. The project owner checked 9 sources live on 2026-10-29. The cloud session cannot reach the job boards to save a new response.
- Replaces: none

## 2026-10-29: Title parser rules

- Milestone: M4b
- Decision: `TitleParser::parse()` reads one job title into a `Seniority` and a `ContractType`. It is pure: no I/O. Words match as whole words, case-insensitive. "Leading" does not match "Lead", and "Seniority" does not match "Senior". "SR." and "Sr" match.
- Decision: seniority words. "Junior", "Jr", "Entry level", "Graduate", "Intern" give `junior`. "Mid", "Mid-level", "Intermediate" give `mid`. "Senior", "Sr" give `senior`. "Lead", "Principal", "Staff", "Head of", "Manager", "Director", "VP" give `lead` (see "Values with no enum case map to existing cases").
- Decision: when a title has two or more levels, the lowest level wins: "Mid/Sr" gives `mid`, "Senior Principal / Director" gives `senior`. This is the same rule as the Himalayas `seniority[]` list.
- Decision: contract type words. "Contract", "Contractor", "Freelance" give `contract`. "Part-time", "Part time", "Parttime" give `part_time`. "Full-time", "Full time", "Fulltime" give `full_time`. "Permanent" is not in the list: in "Graphic Designer (Permanent work from home)" it describes the remote setup.
- Decision: a title with no word from the lists gives `unknown`, never a guess. German words (e.g. "Werkstudent") are not in the lists. The Personio `seniority` field covers German jobs.
- Known false positive: "Manager" gives `lead`, but "Product Manager" or "Account Manager" is often not a lead role.
- Reason: the field mapping sends the seniority of most sources, and the contract type of none, to the title parser. A wrong value is worse than `unknown`. In M6 a structured source field wins over the title.
- Replaces: none

## 2026-10-29: Synthetic titles for the title parser

- Milestone: M4b
- Decision: the title parser tests have one dataset of invented titles, `syntheticTitles`. The project owner approved this exception to the real strings rule (REQUIREMENTS section 14).
- Decision: the dataset is limited to list words that no fixture title has, one row for each word or spelling: "Jr", "Entry level", "Graduate", "Part-time", "Part time", "Parttime", "Full-time", "Full time", "Fulltime". It also has the near-miss words "Leading" and "Seniority". When a real title has one of these words, the real title replaces the synthetic row.
- Decision: all other rows come from `tests/Fixtures/`. A test checks that each row is a real title of its file, and that every fixture title has a row.
- Reason: the cloud session cannot reach the job boards to save new responses, and every list word needs a test.

## 2026-10-29: Salary parser rules

- Milestone: M5
- Decision: `SalaryParser::parse()` reads one free text salary, with an optional currency hint from the source. It gives a `Salary` with `raw` equal to the input, or `null`. It does not change the `Salary` schema.
- Decision: the first amount or range with a currency marker wins. A number with no currency marker is not an amount, e.g. "60000 - 70000" or "401(k)". "60-70k" applies the "k" to both sides.
- Decision: a bare "$" gives USD. With a currency hint, it gives the hint. "¥" can be JPY or CNY, so it needs the hint, else `null`. A code or a symbol with one meaning ("EUR", "€", "A$", "NZ$") wins over the hint. Two different currencies give `null`. The parser never takes the currency from the country.
- Decision: the parser reads the ISO codes of the ECB reference currencies and EUR. A code counts only when it touches an amount: "1000PHP", "PHP 50,000", "50,000 PHP". Otherwise "PHP" is the language. "PHP" before a number below 100 is a version ("PHP 8.3"), not pesos. "₱" gives PHP.
- Decision: the period comes only from the sentence with the amount: "/hour", "p/d", "per month", "/mo", "annual" and similar words. No period word, or two different ones, gives `unknown`. A "k" amount is not read as a year, because that is a guess.
- Decision: one amount gives `min` only. "Up to" before one amount gives `max` and no `min`. A range with min above max gives `null`.
- Decision: an amount that belongs to an allowance or a benefit is not pay: "allowance of 1000PHP", "benefit of 7500PHP", "$500 allowance", "$500 monthly allowance". The parser skips it and reads the next amount. The amount stays pay when "+", "plus", "and", "with" or "&" joins it to the word ("$120k plus benefits"), or when the word is a heading before it ("Compensation & Benefits $120k").
- Decision: "'" and "’" between digit groups are thousands separators, as in Swiss amounts ("CHF 120’000").
- Reason: REQUIREMENTS section 7 says text the rules cannot read gives no value, never a guess. The fixture table in `tests/Unit/Parsers/Salary/salary-table.php` holds the real strings.
- Replaces: none

## 2026-10-29: Exchange rates come from the ECB directly

- Milestone: M5
- Decision: `EcbRatesClient` reads `https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml`, not Frankfurter.
- Reason: Frankfurter only republishes the ECB rates. The direct file has one less third party, which is better for privacy (REQUIREMENTS section 13), and the project already has a real response in `tests/Fixtures/ecb/`.
- Source terms: the ECB publishes the euro foreign exchange reference rates for information only, on TARGET working days at about 16:00 CET. Reuse is free if the ECB is named as the source. The ECB discourages the rates for transactions. The app shows them as an estimate ("≈"). Converted amounts are a change to ECB data, so the app must say that they are converted, and name the ECB as the source of the rates.
- Checked on: 2026-10-30, on a Mac (QA entry "M5: ECB rates terms and live fetch")
- Replaces: none

## 2026-10-29: Exchange rate storage and conversion

- Milestone: M5
- Decision: `ExchangeRates::refresh()` stores the ECB rates in `exchange_rates`, one row per currency, for 1 EUR, with the rate date and the fetch time. A new fetch replaces all rows. EUR has no row: it is the base.
- Decision: after a successful fetch, no new request is made on the same UTC day. A failed fetch logs a warning, keeps the stored rates and does not block a new try. The scheduler (M6b, M7) decides how often it calls `refresh()`.
- Decision: `rateDate()` gives the date of the stored rates for the settings screen (REQUIREMENTS section 7), or `null` before the first fetch.
- Decision: `CurrencyConverter` converts through EUR. A currency with no rate gives `null`, not an error. `DisplayCurrency` holds EUR, USD and GBP.
- Decision: `SalaryValues` holds the filter values: monthly times 12, 13 or 14 payments, hourly times the hours per day (default 8), and the compared value (`max`, else `min`). `UnknownSalaryShare::of()` gives the number and the share of jobs with no salary, for `source_runs`.
- Reason: REQUIREMENTS sections 7 and 9. The values are pure helpers, so the M6 filters can use them without I/O.
- Replaces: none

## 2026-10-30: Synthetic salaries for the salary parser

- Milestone: M5
- Decision: the salary parser tests have one dataset of invented strings, `syntheticSalaries`. The project owner approved this exception to the real strings rule (REQUIREMENTS section 14), as for `syntheticTitles` in M4b.
- Decision: the dataset has five rows. "CHF 120’000 - 140'000" tests the Swiss thousands separators. "up to $120k" tests "up to" with a currency. "$120k + benefits", "$120k plus benefits" and "Compensation & Benefits $120k" test that a salary next to "benefits" stays a salary. The real Remotive text "earn up to 45K in their first year" has no currency, so it gives `null`. When a real string has one of these forms, the real string replaces the synthetic row.
- Reason: no fixture has a Swiss amount or an "up to" amount with a currency. The cloud session cannot reach the job boards to save a new response.
- Replaces: none

## 2026-10-30: TLS trust comes from the NativePHP CA bundle

- Milestone: M5 (QA follow-up)
- Decision: the app trusts the Mozilla CA bundle that `nativephp/desktop` ships (`resources/cacert.pem`). NativePHP copies it into the build and starts the bundled PHP with `curl.cainfo` and `openssl.cafile` set to it. The app has no CA bundle of its own.
- Decision: the app never turns off certificate checks. Never set `verify => false` to fix a certificate error.
- Decision: a release must not ship an old bundle. The release script will check its age.
- Reason: the M5 live fetch failed on a Mac with cURL error 60. The local PHP used an old CA bundle with no "Sectigo Public Server Authentication Root E46", the root of the ECB certificate. The NativePHP bundle of 2026-10-20 has it. The installed app does not use the local PHP or the macOS keychain.
- Replaces: none

## 2026-10-30: M6 split into M6a and M6b

- Milestone: prep for M6
- Decision: M6 becomes M6a (storage, source registry and runner, parser wiring, deduplication, retention, `source_runs` stats) and M6b (filters). The old M6b (`hire:scan`) becomes M6c.
- Decision: a new local phase, UI design, runs with Impeccable in parallel with M6. It must be merged before the M8 brief. The `.impeccable/` folder stays local.
- Reason: M6 was the largest milestone. The filters are pure classes and can run in parallel with the storage work, after the shared `Settings` value object is in main.
- Replaces: the M6 and M6b rows of REQUIREMENTS section 16

## 2026-10-30: Settings value object

- Milestone: prep for M6
- Decision: `App\Settings\Settings` holds every setting of REQUIREMENTS section 8, plus `timezone`, `displayCurrency`, `monthlyPayments`, `hoursPerDay` and `notificationIntervalHours`. It is pure. M6a stores it with `toArray()` and `fromArray()`. M6b reads it.
- Decision: `fromArray()` uses the default for each missing key, so stored settings keep working when a setting is added.
- Decision: `unknown` is not a value for work mode, contract type or seniority settings. The filters decide what to do with unknown values.
- Decision: the default display currency is EUR. The default timezone comes from the Mac at first run, and is UTC when it is missing.
- Reason: M6a and M6b both need the same settings shape. A shared class in main stops the two sessions from making two versions.
- Replaces: none

## 2026-10-30: Salary with no period

- Milestone: prep for M6 (rule for M6b)
- Decision: for the minimum annual salary filter, a salary with period `unknown` counts as yearly when its top amount (`max`, or `min` if no `max`) is 10,000 or more in the original currency. A smaller amount stays unknown and follows the unknown salary setting.
- Decision: the parser does not change. It keeps period `unknown` (never guess). Only the filter applies this rule.
- Reason: most real ranges with no period word are yearly ("$170k - $200k", the Greenhouse pay ranges, the LaraJobs salaries). Small amounts are often hourly or daily rates.
- Replaces: none

## 2026-10-30: On-target earnings (OTE)

- Milestone: prep for M6 (rule for M6b and M8)
- Decision: a pure helper reads "OTE" from `Salary::raw`. The salary filter uses an OTE amount like a salary. M8 shows an "OTE" badge.
- Decision: the job schema does not change. `Salary` has no OTE field.
- Reason: the schema is frozen since M1 (AGENTS.md rule 10), and only one fixture string has OTE. If a source sends OTE as a structured field, a schema change can follow with a new decision.
- Replaces: none


## 2026-10-30: Filter rules for unknown values

- Milestone: M6b
- Decision: an unknown work mode, contract type or seniority always passes these three filters, whatever the setting.
- Decision: when `country` is null (first run not done), the eligibility filter is off. Every job passes it.
- Decision: an amount whose period does not fit the salary filter is not comparable, so it follows `unknownSalary`. Examples: an hourly or daily amount on an employee job, a yearly, monthly or no-period amount on a contract job. The filter does not convert with a number of working days.
- Decision: expiry is a filter. A job with `expiresAt` before now fails. M6a retention deletes old jobs later.
- Reason: approved by the project owner in the M6b brief.
- Replaces: none

## 2026-10-30: Filter result

- Milestone: M6b
- Decision: `JobFilter::evaluate()` runs every filter and gives a `FilterResult`. A job passes when no filter fails. `reasons` holds one `FailReason` for each failing filter. Keywords give two reasons: `keyword_missing` and `keyword_excluded`.
- Decision: a job with unknown eligibility in group mode passes, with `locationNotStated` set. M6c does not count it in the notification.
- Decision: `salaryUnknown` (the "?" badge) is set only when the salary filter is on for the job, the salary is missing or not comparable, and the mode is lenient. When the minimum for the job is null, the filter is off and the flag stays false.
- Decision: `ote` is set when `Salary::raw` has "OTE" or "on target earnings", also when the salary filter is off.
- Decision: the filters are pure classes with static methods. They have no I/O.
- Reason: REQUIREMENTS sections 6 to 8. M6c and M8 need the flags, and the reasons make tests and debugging clear.
- Replaces: none

## 2026-10-30: Timezone overlap on a 24-hour circle

- Milestone: M6b
- Decision: the timezone filter compares offsets on a 24-hour circle, so UTC+14 is UTC-10. It takes the user's offset from `timezone` at the time of the check, so summer time counts. A job with only one timezone value uses it as one offset. A job with no timezone passes.
- Decision: the Himalayas "United States" list is -10 to -5 and 14. `HimalayasSource` stores min -10 and max 14, so the job covers every offset and passes every user. The filter cannot see that 14 means -10. The project owner chose this over a change in `app/Sources/` in M6b.
- Reason: REQUIREMENTS section 8. A later fix in `HimalayasSource` can map 13 and 14 to -11 and -10 before it takes min and max.
- Replaces: none

## 2026-10-30: Keyword matching

- Milestone: M6b
- Decision: a keyword matches when no letter, digit, "_", "#" or "+" touches it. So "c" does not match "c#" or "c++", and "laravel" matches "PHP/Laravel". A hyphen splits words: "stack" matches "Full-Stack".
- Decision: the include and exclude lists match the title, each tag and, when `matchDescription` is on, the description.
- Reason: REQUIREMENTS section 8 says whole words. Real tags such as "C#", "C++" and "node.js" need these rules.
- Replaces: none

## 2026-10-30: Synthetic OTE string

- Milestone: M6b
- Decision: the OTE test has one invented string, "$90k on-target earnings", marked "not from a fixture". No fixture has the hyphenated form. The other OTE rows are real strings from `tests/Fixtures/`.
- Reason: the M6b brief names "on-target earnings", and every rule needs a test. The cloud session cannot reach the job boards to save a new response. This exception needs the approval of the project owner, as for `syntheticTitles` and `syntheticSalaries`.

## 2026-10-30: Parser wiring in the sources

- Milestone: M6a
- Decision: a structured source field always wins. A parser fills a field only when the structured value is missing or `unknown`: Jobicy `jobLevel` "Any", Personio `seniority` "experienced", an empty Himalayas `seniority` list, an empty job type.
- Decision: the location parser reads Greenhouse `location.name`, LaraJobs `job:location`, Jobicy `jobGeo`, Remotive `candidate_required_location` and a We Work Remotely region other than "Anywhere in the World". Jobicy and Remotive keep `remote` as the work mode. Himalayas `locationRestrictions` and the We Work Remotely `country` list go to the country list reader.
- Decision: Ashby eligibility comes from `address.postalAddress.addressCountry` of the job and of each secondary location. A secondary location named after a city ("Berlin") still has its country there. Hybrid and on-site Ashby jobs give `unknown`, as for Lever.
- Decision: the salary parser reads the LaraJobs and Remotive salary fields, the Greenhouse `div.pay-range` text, and the Lever `additionalPlain` text when there is no `salaryRange`. For Lever, `raw` is the first sentence with a salary, not the whole text.
- Decision: the title parser gives the seniority of every source, and the contract type when the source has no value. Personio gets its work mode from the title only. Descriptions are not read for the work mode or the salary.
- Reason: REQUIREMENTS sections 6 and 7 say structured fields first. Text in descriptions gives too many wrong values, and a wrong value is worse than `unknown`.
- Replaces: "Source fields that wait for a parser" (2026-10-29) and "M3 source fields that wait for a parser" (2026-10-29)

## 2026-10-30: Himalayas timezone list that wraps past UTC+12

- Milestone: M6a
- Decision: the span of a Himalayas list is read twice: as stored, and with each offset above 12 minus 24. The smaller span wins. "United States" gives `[-10, -9, -8, -7, -6, -5, 14]`, so -10 to -5, not -10 to 14. `[12, 13]` stays 12 to 13, not -11 to 12. When both spans are the same, the list stays as stored. A list with every whole offset from -11 to +12 is worldwide and stays as it is (-11 to 14).
- Decision: the tests have one dataset of invented lists, `syntheticTimezoneLists`: `[5, ..., 13]` and `[12, 13]`. The project owner approved this exception to the real strings rule, as for `syntheticTitles`. No fixture has a list that reaches +13 legitimately.
- Decision: the M6b timezone filter treats a span of 24 hours or more as all offsets.
- Reason: UTC+14 is the same clock time as UTC-10. Without the rule, the US list looks like a worldwide list. Minus 24 for every list made `[12, 13]` almost the whole day, so the smaller span wins (review of #16). The project owner approved this scope in the M6a session.
- Replaces: none

## 2026-10-30: Source registry and runner

- Milestone: M6a
- Decision: `SourceRegistry` lists the v1 sources by key: `ats`, `larajobs`, `jobicy`, `remotive`, `himalayas`, `weworkremotely`. `enabled()` leaves out the keys in `disabledSources`. `SettingsStore::save()` rejects a key that the registry does not know. The runner ignores such a key.
- Decision: `SourceRunner::run()` runs a source when its last run started at least `minInterval()` ago, also after a failed run. `$since` is the start of the last successful run, so a failed run loses no jobs.
- Decision: each run stores one `source_runs` row. `items_fetched` counts the raw items, `items_skipped` the items that threw `SkipItem`. The shares come from `UnknownEligibilityShare` and `UnknownSalaryShare`. A failed run has 0 items and no shares.
- Decision: the error kind is `certificate` for a TLS cURL error (35, 51, 53, 54, 58, 59, 60, 64, 66, 77, 80, 82, 83, 90, 91), `http` for an HTTP status or another connection error, `parse` for `UnreadableResponse` or bad JSON, and `other` for the rest. The board sources now throw `UnreadableResponse`, a `RuntimeException`, for a response that is not RSS or has no job list.
- Decision: the ATS source keeps its own error for each company in `ats_companies`. A failing company does not make the `ats` run fail.
- Reason: REQUIREMENTS sections 9 and 10. M8 shows "certificate error" from the error kind.
- Replaces: none

## 2026-10-30: Job storage, deduplication and retention

- Milestone: M6a
- Decision: `JobStore::save()` updates a job with the same source and source job ID. A job from another source with the same fingerprint, first stored in the last 14 days (`created_at`), is stored in `job_sources` as "also on", with its own source link, apply link, attribution and fetch time. The first job keeps its links.
- Decision: when a job is seen again through an "also on" source, the job's `fetched_at` moves to that fetch. Retention (`JobStore::prune()`) deletes the jobs with `fetched_at` older than 30 days, and their `job_sources` rows.
- Decision: `jobs.notified_at` stores when a job was in a notification. `JobStore::notNotified()` reads the jobs with no `notified_at`. M6c and M7 set it and call `prune()`.
- Decision: the job model is `App\Models\Job`. The model for `job_sources` is `App\Models\AlsoOnSource`, because `JobSource` is the source interface.
- Reason: REQUIREMENTS section 9. A job that a second board still lists must not be deleted and notified again as new.
- Replaces: none

## 2026-10-30: Settings storage and the Mac timezone

- Milestone: M6a
- Decision: `SettingsStore` stores `Settings::toArray()` as JSON in one row of the `settings` table. Before the first save, `load()` gives `Settings::defaults()` with the Mac timezone.
- Decision: `SystemTimezone` reads the target of the `/etc/localtime` link and takes the part after `/zoneinfo/`, e.g. "Europe/Rome". A missing link, another path or a name that PHP does not list gives `UTC`.
- Reason: the "Settings value object" entry says the default timezone comes from the Mac at first run.
- Replaces: none

## 2026-11-01: One synthetic OTE test string

- Milestone: M6b
- Decision: `OteTest` keeps the string "$90k on-target earnings", which is in no fixture. It is the only exception to the rule that tests use real strings, and its test name says "not from a fixture".
- Reason: the helper reads "on-target" with a hyphen on purpose, and no fixture has that spelling. Without this string no test checks it.
- Replaces: none

## 2026-11-01: MIT license

- Milestone: none (open question in REQUIREMENTS section 17)
- Decision: the app is released under the MIT license.
- Reason: the paid Premium part is a separate server and a curated company list (data, not code), so nothing paid lives in this repository. The name and the icon need their own trademark notice, because MIT does not cover them.
- Replaces: none

## 2026-11-01: The scan command

- Milestone: M6c
- Decision: `php artisan hire:scan` runs one scan in this order: refresh the exchange rates, run the due sources, prune, filter the jobs with no `notified_at`, print the matches, then send the summary notification when it is due. The service is `App\Scan\Scan`, so M7 can run the same scan from the NativePHP scheduler.
- Decision: a failed rates fetch, a failed source and a failed notification do not stop the scan. They are in the output. The exit code is 0.
- Decision: the command is always isolated (`Isolatable`), also without the `--isolated` option. A second scan that starts while one runs exits at once with code 0. The lock is in the cache store.
- Decision: `--no-notify` prints the matches, sends nothing and sets no `notified_at`.
- Reason: approved in the M6c brief. Cron can start a scan every few minutes, and a slow scan must not run twice.
- Replaces: none

## 2026-11-01: "Also on" and disabled sources

- Milestone: M6c
- Decision: a job passes the source filter when its main source or one of its "also on" sources is enabled. `SourceFilter::passes()` and `JobFilter::evaluate()` take an optional last argument, the list of the "also on" source keys. Existing calls keep working.
- Decision: the output and the notification use the links of the first enabled source: the main source, else the first "also on" source in stored order. The stored apply links never change (AGENTS.md rule 11).
- Reason: approved in the M6c brief. A job that a disabled board found first is still a real job on an enabled board.
- Replaces: none

## 2026-11-01: Notification interval and notified_at

- Milestone: M6c
- Decision: a notification is sent when there are new matches and no notification was sent in the last `notificationIntervalHours`. A scan with 0 matches sends nothing and does not reset the interval. Matches found between two notifications wait for the next one.
- Decision: when a notification is sent, `notified_at` is set on the jobs that it counts, and only on them. Jobs that fail the filters and jobs in the "Location not stated" group keep `notified_at` null, so a change in the settings can still show them later.
- Decision: when the notifier fails, the scan sets no `notified_at` and stores no notification. The next scan tries again.
- Reason: approved in the M6c brief (REQUIREMENTS section 10).
- Replaces: none

## 2026-11-01: Summary text and the macOS notifier

- Milestone: M6c
- Decision: the text is "6 new jobs: 4 Laravel, 2 PHP". Each job counts once, under the first include keyword that it matches, in the order of `keywordsInclude`. A keyword with 0 jobs is left out. With no include keywords the text is "6 new jobs". One job gives "1 new job". `KeywordFilter::firstInclude()` gives the keyword, with the same rules as the filter.
- Decision: the `Notifier` interface has one method, `send()`. `MacNotifier` runs `osascript` with `display notification` and the title "Sudo Hire". The text is an argument of the script (`on run argv`), and `Process` gets an argument list, so quotes in a job title cannot break the script.
- Decision: the `sent_notifications` table stores the sent time, the job count and the text of each sent notification. The scan reads the last sent time for the interval. M7 and M8 read it too. The model is `App\Models\SentNotification`, next to the other models.
- Reason: REQUIREMENTS section 10. A shell string or a text inside the script breaks on a title with `"` or `\`.
- Replaces: none

## 2026-11-01: Error kind for each ATS company

- Milestone: M6c
- Decision: `ReadFailed` keeps the cause as the previous exception. `ReadFailed::kind()` gives the `RunErrorKind` of the cause: an HTTP status is `http`, bad JSON or a response with no job list is `parse`, a TLS cURL error is `certificate`. With no cause, the kind is `other`.
- Decision: `ats_companies.last_error_kind` stores the kind next to `last_error`. A successful read clears both. `CompanyList::recordError()` takes the kind. `ats:list` shows "Error (certificate): ...". A row from before this change has no kind and shows "other".
- Reason: one company with a certificate error was stored as text only, so M8 could not show "certificate error" for it.
- Replaces: none

## 2026-11-01: Posted within 14 and 30 days

- Milestone: UI design
- Decision: "Posted within" accepts 24, 72, 168, 336 and 720 hours: 24 hours, 3 days, 7 days, 14 days and 30 days. The default stays 3 days.
- Decision: the timezone filter is named "Time difference" in REQUIREMENTS section 8 and in the error message. The settings key stays `timezoneOverlapHours`, so stored settings keep working.
- Reason: the UI design offers 14 and 30 days for users who check less often. Jobs are kept for 30 days after they were last seen, so 30 days is the longest useful window. The filter keeps a job when its timezones are within ±N hours of the user's UTC offset, so "time difference" describes it better than "overlap".
- Replaces: "24h, 3 days, 7 days" in REQUIREMENTS section 8

## 2026-11-01: Scan cadence in the app

- Milestone: M7
- Decision: `routes/console.php` schedules `hire:scan` every 5 minutes. NativePHP starts `schedule:run` every minute while the app runs, so the schedule runs only while the app runs.
- Decision: the schedule has no `withoutOverlapping()`. The command is isolated already.
- Decision: the schedule uses `runInBackground()`, so `schedule:run` returns at once and the scan runs as its own process. Reason: before each run, NativePHP stops the previous `schedule:run` process. A foreground scan that runs for more than 60 seconds then continues only as an orphan process.
- Decision: a scan fetches only the sources that are due, so a scan with no due source ends in under a second. After the Mac wakes, the next scan starts within 5 minutes.
- Reason: approved in the M7 brief (REQUIREMENTS section 10).
- Replaces: none

## 2026-11-01: No country, no notification

- Milestone: M7
- Decision: when the settings have no country, a scan runs the sources, prunes and filters as usual, but sends no notification and sets no `notified_at`. The outcome is `NotificationOutcome::NoCountry`. `hire:scan` prints "No notification: set your country first." With `--no-notify`, the outcome stays "off".
- Decision: the M6c tests now store the country IT, and their jobs are open worldwide unless a test changes it. The M6c rules do not change.
- Reason: approved in the M7 brief. Without a country the eligibility filter is off, so the matches cannot be trusted. M8 onboarding sets the country.
- Replaces: none

## 2026-11-01: Notifier choice and the click on the summary

- Milestone: M7
- Decision: inside the app (`nativephp-internal.running` is true), `AppServiceProvider` binds `Notifier` to `NativeNotifier`. Outside the app (terminal, cron, launchd), it binds `MacNotifier` (`osascript`). This binding replaces the `#[Bind]` attribute on `Notifier`.
- Decision: `NativeNotifier` posts the title "Sudo Hire", the summary text and the fixed reference `sudo-hire-summary` to the internal API. It gets `Native\Desktop\Notification`, the class behind the facade, so PHPStan knows the types. The internal API gives the reference back only when it shows the notification. A missing reference or a failed request throws `NotificationFailed`, so the M6c rules apply.
- Decision: the listener `App\Listeners\OpenListOnSummaryClick` opens the menu bar list with `MenuBar::show()` when the reference of `NotificationClicked` is `sudo-hire-summary`. It ignores other references. Laravel finds the listener in `app/Listeners`, so no provider registers it.
- Reason: approved in the M7 brief (REQUIREMENTS section 10). The fixed reference is the only link between the notification and the click event.
- Replaces: none

## 2026-11-02: Job states

- Milestone: M8a
- Decision: `jobs` has `seen_at`, `state` and `state_at`. The state is `saved`, `applied`, `hidden` or null (New). A job has one state at a time. A new state replaces the old one.
- Decision: setting the same state again clears it, so the job moves back to New. A null state also clears it. `JobStore::setState()` applies these rules.
- Decision: any state change sets `seen_at`, if it is not set yet.
- Decision: a source that lists the job again does not change its state or `seen_at`.
- Reason: approved in the M8a brief (PRODUCT.md "Job states", DESIGN.md "Row states").
- Replaces: none

## 2026-11-02: Seen on close

- Milestone: M8a
- Decision: on `MenuBarShown`, the app stores the time the list opened in the cache. On `MenuBarHidden`, it sets `seen_at` on the New matches with no `seen_at` that were stored at or before that time. Then it clears the stored time.
- Decision: a job stored while the list is open stays unseen. Jobs in the "Location not stated" group and jobs that fail the filters do not change.
- Decision: any action on a row also marks it as seen (see "Job states").
- Reason: approved in the M8a brief. It replaces the open item "When rows become seen" of DESIGN.md. The user sees the new jobs before they lose their unseen dot.
- Replaces: none

## 2026-11-02: Retention of saved, applied and hidden jobs

- Milestone: M8a
- Decision: `JobStore::prune()` never deletes a saved or an applied job, or its "also on" rows. A hidden job is deleted after 30 days, as a job with no state.
- Decision: when a job becomes hidden, the `hidden_jobs` table stores one row for each source key of the job (its own key and the key of each "also on" row), with the fingerprint of the job. `JobStore::save()` skips a job whose key is in `hidden_jobs`, with no time limit. It also skips a job with the same fingerprint while the tombstone is younger than `JobStore::DUPLICATE_DAYS` (14 days), the window of deduplication. So the hidden job is not updated, it gets no new "also on" row, and its source keys do not come back after retention. A later post with the same company and title from another source is stored again after 14 days (approved by the project owner on 2026-11-02).
- Decision: undo (any change away from hidden) deletes the tombstones of that job. Tombstones have no retention in v1.
- Reason: approved in the M8a brief. "Not for me" means "never shown again" (PRODUCT.md), also when the job comes back from another board.
- Replaces: the retention rule of "Job storage, deduplication and retention" (2026-10-30), for saved and applied jobs

## 2026-11-02: The list service and the stamp

- Milestone: M8a
- Decision: `App\List\JobList` builds the list from all stored jobs and the stored settings. New has the matches with no state, newest post first. The "Location not stated" group is apart. Saved and Applied have the jobs in that state, newest state first, also when they fail the filters now. Hidden jobs are not in the list.
- Decision: the New count does not include the group. The group has its own count.
- Decision: `App\Scan\JobMatcher` runs the filters and picks the links of the first enabled source. The scan and the list use it. When no source of a job is enabled, a saved or applied job shows the links of its main source.
- Decision: the stamp kind is `admitted` or `not_stated`. A job with unknown eligibility is `not_stated`, also in lenient mode. A saved or applied job that the eligibility filter refuses now is `refused`, with no place.
- Decision: the place of an admitted stamp is the user's country code when the job lists it, else the name of the first region of the job that admits the user. A worldwide job, or a job with no matching region name, gives the user's country code, for example "IT". With no country in the settings, the place is "Anywhere". "Location" is only for the `not_stated` kind (approved by the project owner on 2026-11-02).
- Decision: the salary of a row has the original text as the source gives it, and the converted amount from `SalaryText::forList()`, as DESIGN.md "Job row" shows it: "≈", the symbol of the display currency, "k" for thousands and the period, for example "≈ €66k - €84k / year". From 10,000 the amount is in whole thousands, from 1,000 in thousands with one decimal ("€5.5k"), and below 1,000 a whole number ("€625 / day"). The period is the period of the source. The app does not convert a monthly amount to a yearly one. With the same currency, or with no rate, there is no converted text. The terminal output of `hire:scan` keeps its format (approved by the project owner on 2026-11-02).
- Decision: the source name is the attribution without "via". The ATS reader is "Company careers" in the source list. A job row keeps the attribution of the company, e.g. "via Acme careers".
- Reason: approved in the M8a brief. The UI does not filter or parse (AGENTS.md), so the API gives the rows ready to show.
- Replaces: none

## 2026-11-02: The JSON API and CSRF

- Milestone: M8a
- Decision: the API is in `routes/web.php` under `/api`, with the web middleware. Every write needs the CSRF token. The page has the token in the `csrf-token` meta tag, and the UI sends it in the `X-CSRF-TOKEN` header. The API has no token login: only the app page calls it.
- Decision: the keys are in camelCase, as in `Settings::toArray()`. Times are ISO 8601 in UTC.
- Decision: errors are JSON with a plain `message`. Validation errors are 422. An unknown job or company is 404 with "Job not found." or "Company not found.".
- Decision: `PUT /api/settings` merges the request with the stored settings, then validates with `Settings::fromArray()`. An `InvalidSettings` or `UnknownSource` message gives 422. A setting that the request does not send keeps its stored value.
- Decision: `PUT /api/jobs/{job}/state` gives the new state and the counts. `POST /api/companies` uses the `ats:add` rules and messages.
- Decision: `GET /api/sources` gives the job count of a source as the stored jobs and "also on" rows of that source. `GET /api/status` gives the last scan from the cache. The new `ScanFinished` event at the end of `Scan::run()` stores it. The next scan is 5 minutes after the last start.
- Decision: `GET /api/diagnostics` gives the app version, the macOS version (`sw_vers`), the PHP version and, for each source, its key, enabled, last run, last success and error kind. It gives no error message, because a message can have a URL.
- Reason: approved in the M8a brief. The web middleware gives the session and CSRF with no extra setup.
- Replaces: none

## 2026-11-02: Unseen badge

- Milestone: M8a
- Decision: the menu bar label shows the number of unseen New matches, without the "Location not stated" group. At 0 the label is empty.
- Decision: `App\List\UnseenBadge` sets the label only inside the app (`nativephp-internal.running`). It runs after each scan (`ScanFinished`), after `MenuBarHidden`, after each state change and after a change of the settings. A failed label is logged and does not stop the action.
- Reason: approved in the M8a brief (REQUIREMENTS section 11). The group is never notified, so it does not count as new either. The settings change the matches, so the badge follows them.
- Replaces: none

## 2026-11-02: File session driver

- Milestone: M8a
- Decision: `.env.example` sets `SESSION_DRIVER=file`. `PRIVACY.md` no longer says that the session data has the local address of the Mac.
- Reason: approved in the M8a brief. The database driver stores the IP address and the user agent in the `sessions` table. The file driver stores only the session data.
- Replaces: none

## 2026-11-02: Notifications count only new jobs

- Milestone: M8a (review follow-up)
- Decision: `JobStore::notNotified()` gives only the jobs with no `notified_at`, no state and no `seen_at`. So a job that the user saved, applied, hid or saw in the list before its first summary is not in the notification. Its `notified_at` stays null.
- Decision: the other rules of the M6c entry do not change.
- Reason: approved by the project owner on 2026-11-02. A notification about a job that the user already acted on is noise.
- Replaces: the rule "when a notification is sent, `notified_at` is set on the jobs that it counts" of "Notification interval and notified_at" (2026-11-01), for jobs with a state or a seen time

## 2026-11-02: The menu bar list in Vue

- Milestone: M8b
- Decision: the popover is one Vue component tree in `resources/js/list/`. The colors, sizes and texts come from `docs/DESIGN.md` and the prototype. The tokens are CSS variables in `resources/css/app.css`, with dark values through `prefers-color-scheme`, mapped into the Tailwind theme. The popover is 400 by 640.
- Decision: the list loads when the page opens, when the popover gets the focus or becomes visible, and after each action. It does not poll while the popover is hidden.
- Decision: the stamp tilt is computed in the browser from the job id, with the DESIGN.md formula. The stamp texts come from the API kind and place.
- Reason: approved in the M8b brief. The business logic stays in PHP: the list only shows the API data.
- Replaces: none

## 2026-11-02: App actions from the web view

- Milestone: M8b
- Decision: the web view cannot open the browser, open a window or quit. It calls `POST /api/app/open-url`, `/api/app/settings` and `/api/app/quit`, and PHP uses the NativePHP `Shell`, `WindowManager` and `App` classes. `open-url` accepts only http and https links.
- Decision: the settings window loads `/settings`, a placeholder page until M8c.
- Decision: when the PHP server does not answer, the list shows the stopped state. Its button quits through `window.Native.contextMenu` with Electron's `quit` role, because the API is gone. The web view cannot restart the app: `app.relaunch()` is only behind the NativePHP internal API, and the preload script exposes no relaunch. The stopped state says "Quit Sudo Hire and open it again."
- Reason: the project owner chose "quit, then reopen" on 2026-11-02, over a patch of the NativePHP preload script.
- Replaces: the "Restart Sudo Hire" button of DESIGN.md "Empty and stopped states"

## 2026-11-02: OTE badge, keyboard and monthly salaries in the list

- Milestone: M8b
- Decision: the OTE badge looks like the "also on" badge, after the salary.
- Decision: keyboard movement in the list (arrow keys, Return, S) comes after v1. v1 has Tab order and focus rings.
- Decision: the list shows a monthly salary as a year, with the monthly payments of the settings, also in the same currency: "€5,500 - €7,000 / month" then "≈ €66k - €84k / year". Other periods show the converted amount only when the currency is different. `hire:scan` keeps its own format.
- Reason: the project owner decided the badge and the keyboard on 2026-11-02. The monthly example is in DESIGN.md "Job row".
- Replaces: none

## 2026-11-02: Apply does not mark a job as applied

- Milestone: M8b
- Decision: Apply opens the apply link in the default browser and does not change the job. Applied is a separate action that moves the job to the Applied tab.
- Reason: opening a link is not applying. A job marked by mistake would leave New, and the user would not see it again there. Some job sites mark a job as applied on Apply, so the difference is noted in DESIGN.md "Job row", and a prompt after Apply is an open item.
- Replaces: none

## 2026-11-02: Hidden jobs come back from Settings

- Milestone: M8c
- Decision: the Jobs pane of the settings window lists the hidden jobs, each with "Show again". "Show again" works like Undo: it clears the state and deletes the tombstones of the job, so the job goes back to New.
- Decision: the menu bar list keeps three tabs. Hidden jobs have no tab.
- Reason: chosen by the project owner on 2026-11-02 during the M8b checks. After the Undo toast closes, a hidden job could not come back.
- Replaces: none

## 2026-11-02: The docs site lives in a separate repository

- Milestone: Site
- Decision: the docs site of sudohire.dev is built in the repository `albertoarena/sudo-hire-docs`, apart from the app. It is private until the release.
- Decision: the site has the download, install and first run, the settings panes, the sources with their attribution, the privacy policy, troubleshooting and how to report a problem.
- Decision: M9 needs the site. The release links to sudohire.dev for the download and the privacy policy. In M8c, "Read the privacy policy" and the troubleshooting help link to the site.
- Reason: the project owner decided on 2026-11-02 that Sudo Hire is not released without a docs site. A separate repository keeps the site's content license, tests and deploys apart from the app.
- Replaces: the link to PRIVACY.md in the Privacy pane (DESIGN.md "Settings window"), once the site is live

## 2026-11-02: Location limits in the title

- Milestone: fix (title location limits)
- Decision: `TitleLocationParser` reads a location limit from the job title, with the country and region tables of M4. It reads only the text in brackets and the parts after the role name, split at a dash with spaces around it (hyphen, en dash or em dash), at " | " and at ", ". The role name before the first separator is never read, so "Global Payments Engineer" and "Germany Sales Lead" give nothing.
- Decision: each part goes to the location parser after the word "eligible" is removed. A region ("EMEA", "LATAM", "APAC", "EU", "Europe") counts alone: "Senior PHP Developer (LATAM)". A country counts only when the title has "remote", "eligible", "only" or "based": "(Remote Eligible in Bulgaria)", "Remote - Germany", "(US only)". "Product Support Specialist - Australia" gives nothing.
- Decision: a part must be a place as a whole. "Bank of Ireland" and "New Zealand Post" give nothing. A part that is hybrid or on-site, that excludes places ("not US"), or that says worldwide also gives nothing.
- Decision: the title narrows, never widens. When the title names no place, the source eligibility stays. A worldwide or unknown source takes the title places. When both name places, the result is the places in both lists, compared country by country. When the result is the whole of one list, that list is kept as it is, with its region keys. Example: the Ashby job "Senior Software Engineer, Product Engineering - EU" also lists Switzerland, so it keeps `eu` only.
- Decision: when the two lists have no place in common, the result is `unknown`, not a refusal. The job goes to the "Location not stated" group, so the user still sees it.
- Decision: `TitleLimits::eligibility()` is the one shared place. Every source calls it with its eligibility, work mode and title. The ATS readers call it once, where the job is built. Hybrid and on-site jobs keep their eligibility: their places are offices (see "Location parser rules").
- Decision: the title tests use short inline titles, as the brief of this fix asks. Fixture titles that name a limit are in `title-location-table.php`. A test checks that every other fixture title gives nothing.
- Reason: a We Work Remotely job titled "Principal Software Engineer (Remote Eligible in Bulgaria)" had a worldwide region and showed as a match for a user in Italy. A wrong match is worse than `unknown`. The job schema and the `JobSource` interface do not change.
- Replaces: none

## 2026-11-02: Settings save on change

- Milestone: M8c
- Decision: the settings window has no Save button. A checkbox, a select, a switch, the slider and a chip field save through `PUT /api/settings` when they change. A text or number field saves on blur or Enter. A 422 keeps the stored value and shows the API message under the field.
- Decision: the window title follows the pane. Electron ignores the page title, so the page calls `POST /api/app/settings-title` with the pane key, and PHP sets the title from a fixed list.
- Reason: approved in the M8c brief. A macOS settings window applies a change at once.
- Replaces: none

## 2026-11-02: The hidden jobs list and older hidden jobs

- Milestone: M8c
- Decision: `GET /api/hidden-jobs` gives the stored hidden jobs (title, company, hidden at), newest first, and the count of older hidden jobs. An older hidden job is a job that retention deleted: only its tombstones stay. The count is the number of jobs, not of tombstones.
- Decision: "Show again" calls the state endpoint with `hidden` again, so the job goes back to New and its tombstones are deleted.
- Decision: "Clear older hidden jobs (N)" shows only when N is more than 0. After a confirmation in the page, `DELETE /api/hidden-jobs/older` deletes the tombstones of the deleted jobs. A source that still lists one of these jobs can store it again. The tombstones of a stored hidden job stay. `PRIVACY.md` does not change.
- Reason: chosen by the project owner on 2026-11-02. Tombstones have no retention in v1, so the user needs a way to remove them.
- Replaces: none

## 2026-11-02: App actions of the settings window

- Milestone: M8c
- Decision: `POST /api/app/show-data-folder` shows the database file of the app in Finder. `POST /api/app/open-log-folder` opens the folder of the log file. `POST /api/app/copy-diagnostics` puts the diagnostic report, as plain text, on the clipboard.
- Decision: PHP gives every path and the report text. The actions ignore any path or text in the request.
- Decision: `App\List\DiagnosticReport` builds the report for `GET /api/diagnostics` and for the clipboard, with the same rule: no settings, keywords, companies, jobs or error messages.
- Reason: approved in the M8c brief. A path from the web view could open any file.
- Replaces: none

## 2026-11-02: Links to sudohire.dev

- Milestone: M8c
- Decision: "Read the privacy policy" opens https://sudohire.dev/privacy/. Troubleshooting links to https://sudohire.dev/help/, and "Report a problem" opens a new issue on the sudo-hire repository on GitHub. All open in the default browser through `POST /api/app/open-url`.
- Decision: the links are constants in `resources/js/shared/links.ts`, the one place for web links in the UI. M9 checks that the site has these pages.
- Reason: decided by the project owner on 2026-11-02 (see "The docs site lives in a separate repository"). The paths are a proposal, because the site has no page list yet.
- Replaces: none

## 2026-11-02: Parts that the first run shares

- Milestone: M8c
- Decision: the country combobox, the chip field and the stamp preview are components in `resources/js/shared/`, with the API client, the icons, the stamp and the stopped state. M8d uses them for the first run.
- Decision: `GET /api/stamp-preview?country=XX` gives the stamps of two stored jobs for a country and does not save the country. It prefers one job that the country admits and one that it refuses, newest post first. With fewer jobs it gives what there is, and a job with no stated location comes last. Hidden jobs are left out.
- Reason: approved in the M8c brief. The preview helps the user check the country before the first scan of new settings.
- Replaces: none

## 2026-11-02: The menu bar mark

- Milestone: design (menu bar mark)
- Decision: the menu bar shows the stamp mark of DESIGN.md, not the default NativePHP icon. The source is `resources/icons/menu-bar-mark.svg`, drawn from the approved prototype. The app uses two PNG images rendered from it: `public/menuBarIconTemplate.png` at 15 by 15 and `menuBarIconTemplate@2x.png` at 30 by 30.
- Decision: the mark is black on transparent, and the file name ends in "Template". macOS then tints it for light and dark menu bars. Electron picks the 2x image by its name.
- Decision: the SVG has the `rsvg-convert` commands that render the two images. A test checks the path and the sizes.
- Reason: chosen by the project owner on 2026-11-02, before M8d, so that the Mac checks show the real mark. The app icon stays in M9.
- Replaces: the menu bar mark part of the DESIGN.md open item "Final icon drawing"

## 2026-11-02: A country list keeps its known names

- Milestone: fix (Himalayas country names)
- Decision: `countryList()` and `countryText()` skip a name that is not in the country or region tables, and keep the other names. A list with no known name gives `unknown`. An empty list still gives `unknown`, so the source applies its own rule.
- Decision: the country table has four more spellings that Himalayas sends: "Virgin Islands, British" (VG), "Virgin Islands, U.S." (VI), "Holy See (Vatican City State)" (VA) and "Congo, The Democratic Republic of the" (CD). Kosovo has no ISO 3166-1 code, so it is not in the table and is skipped.
- Reason: two Latin America jobs on Himalayas listed 45 countries. Two spellings were not in the table, so the old rule gave `unknown` and the jobs showed "Location not stated" for every user. A name that is skipped can only make the list smaller, never admit a country that the job did not name. Decided by the project owner on 2026-11-02.
- Replaces: the `countryList()` rule "One name that is not in the tables gives `unknown`" of "Location parser rules" (2026-10-29)

## 2026-11-02: Identical copies on the same board

- Milestone: fix (same-board copies)
- Decision: `JobStore::save()` stores a new job as a `job_sources` row of a stored job from the same source when the two are identical copies. Identical means: the same fingerprint, the stored job was first stored in the last `JobStore::DUPLICATE_DAYS` (14 days, `created_at`), the same eligibility (type, regions and countries, compared as sets) and the same salary (minimum, maximum, currency and period; no salary on both is the same). The row goes to the first such job (lowest id).
- Decision: the copy row keeps its own source job ID, source link, apply link, attribution and fetch time. The job keeps the links of its first copy (AGENTS.md rule 11). A fetch of the copy moves the job's `fetched_at`, as for another source.
- Decision: a copy with another eligibility or another salary is stored as its own job. The store does not merge them and does not widen the eligibility of a stored job.
- Decision: a job from another source is checked first, as before. So a same-board copy goes to the job of another source with the same fingerprint when there is one.
- Decision: "also on" names each other source once and never names the shown source. A job with only same-board copies shows no "also on". A copy row adds no new source key, so the source filter does not change. Hiding a job stores a tombstone for each copy key, as for each "also on" key.
- Decision: rows stored before this change are not migrated. Separate copies already stored stay until retention deletes them.
- Reason: a board can list one job under more than one URL (one job on We Work Remotely under two slugs, one role on Himalayas once for each country). The user saw the same job more than once, and a notification counted it more than once.
- Replaces: the part of "Job storage, deduplication and retention" (2026-10-30) that merges only a job from another source.

## 2026-11-03: The stamp place with no country

- Milestone: fix (stamp with no country)
- Decision: with no country in the settings, the place of an admitted stamp is the place of the job. A worldwide job gives "Anywhere". A restricted job gives its first listed country code, for example "BG". A restricted job with regions only gives the name of its first region.
- Decision: the region name comes from `Stamp::NAMES`. A key with no name gets one from the key: the words split on "_", each with a capital first letter ("northern_europe" gives "Northern Europe").
- Decision: the kind stays `admitted`, because with no country the eligibility filter is off. A job with unknown eligibility stays "LOCATION / NOT STATED".
- Reason: "Anywhere" on every stamp said that a job open only in Bulgaria was open everywhere. The user reads the stamp first, so it must not claim more than the job says.
- Replaces: the sentence "With no country in the settings, the place is "Anywhere"" of "The list service and the stamp" (2026-11-02)

## 2026-11-02: The first run

- Milestone: M8d
- Decision: the app opens the first run on start while no country is stored (`NativeAppServiceProvider`). The first run stores nothing until "Run first scan". That button saves the country, keywords and work modes in one `PUT /api/settings`. Closing the window earlier stores nothing, so the first run opens again at the next start. The menu bar list and Settings keep working.
- Decision: the country preset comes from the region of the Mac. `SystemCountry` reads `defaults read -g AppleLocale`: "en_IT@currency=eur" gives IT, and a region override ("rg=itzzzz") wins. A region that is not in the country table, a language with no region or a numeric region ("en_001") gives no preset. `GET /api/country-preset` gives it and stores nothing.
- Reason: decided by the project owner on 2026-11-02 (M8d brief).
- Replaces: none

## 2026-11-02: The first scan

- Milestone: M8d
- Decision: the first save with a country sets `settings.first_scan_pending`. The next scan with notifications on is the first scan: it sends no notification, sets `notified_at` on the jobs that it counts, and clears the mark (`NotificationOutcome::FirstScan`). So the next summary has only newer jobs. With `--no-notify` the mark stays. Settings stored before M8d keep false.
- Decision: the mark belongs to the stored settings, not to one command. If the scheduled `hire:scan` runs before the scan that the first run starts, it is the first scan. A `--first` option would fail here: the started scan exits when the scheduled one holds the lock, and the next scan would notify every job.
- Decision: `POST /api/first-scan` starts `php artisan hire:scan` as a NativePHP child process (alias `first-scan`) and returns 202 at once: a scan can take a minute. `hire:scan` is isolated, so it never runs at the same time as the scheduled scan. With no country it gives 422.
- Decision: `GET /api/first-scan` gives each enabled source as waiting, reading, done or failed, from `source_runs`. A run that has not finished is reading. A run that started after the first scan started, or the last run of a source that is not due, gives done or failed: the scheduler often reads every source while the first run is open, before a country is stored. Other sources are waiting. The page asks once a second until no source is waiting or reading.
- Reason: the project owner decided on 2026-11-02 that the first scan sends no notification and that its jobs count as notified. M8d chose the mechanism.
- Replaces: none

## 2026-11-02: M8d follow-ups of the M8c checks

- Milestone: M8d
- Decision: the country combobox opens the whole table on focus, with the current country active and in view, and the text selected. Typing replaces the text and filters, with at most 40 matches. The settings window and the first run share it.
- Decision: Hidden jobs in Settings, Jobs shows the 10 newest, then "Show all N hidden jobs" shows the rest in place.
- Decision: "Details" in the source warning of the list opens Settings on Sources (`POST /api/app/settings` with `pane`). The page reads `?pane=`. When the settings window is open already, PHP sends it the new URL (`window/url`), because opening an open window only brings it to the front.
- Decision: a failed source waits its full minimum interval, as before. The error sentence in Sources gives the time of the next try, the last run plus the interval ("Sudo Hire tries again at 21:00."), and "at the next scan" when that time has passed.
- Decision: the 422 text for a minimum of 0 says "Leave the field empty to turn it off.", and the Privacy pane links to the privacy page of sudohire.dev (REQUIREMENTS section 11).
- Reason: the M8c Mac checks and a failed We Work Remotely run on 2026-11-02. Decided by the project owner on 2026-11-02.
- Replaces: none

## 2026-11-03: The Jobicy endpoint and interval

- Milestone: fix (Jobicy)
- Decision: the Jobicy source reads `https://jobicy.com/api/jobs?count=100&industry=engineering`, one page, every 6 hours. `/api/jobs` is the endpoint in the Jobicy docs; the old v2 address stays only for compatibility. The response has the same fields and job IDs.
- Decision: no tag. The keyword filter (section 8) chooses the jobs, as for the other boards.
- Decision: the job level "Entry-Level, Junior" gives `junior`.
- Reason: on 2026-11-03, `tag=php` gave no jobs on both endpoints, so the source read nothing. `industry=engineering` gave 209 jobs in the 7-day window of the API, about 30 a day, so one page of 100 covers 3 days. The Jobicy fair use terms say a few reads a day are enough, and never more than one an hour. Decided by the project owner on 2026-11-03.
- Replaces: the Jobicy request in "ATS endpoint parameters" and "Source terms of Jobicy" (2026-10-29: `?count=50&tag=php`, every hour), and the "1 hour" and `tag=php` notes of REQUIREMENTS section 4

## 2026-11-03: The app icon

- Milestone: M9a
- Decision: the source of the app icon is `resources/icons/app-icon.svg`, concept "Stamp on cover" from the approved prototype. It sits on the macOS icon grid: a rounded square of 824 by 824 on 1024, with a corner radius of 184, and a transparent margin.
- Decision: `scripts/render-app-icon.sh` renders it with `rsvg-convert` and `iconutil` to `public/icon.png` (1024 by 1024) and `public/icon.icns` (16 to 1024 pixels, with the 2x sizes). NativePHP copies these two files into the build. The rendered files are in git, so a build needs no extra tool. A test checks the path and the size.
- Reason: approved in the M9 brief. The menu bar mark does not change.
- Replaces: the app icon part of the DESIGN.md open item "Final icon drawing"

## 2026-11-03: The updater

- Milestone: M9a
- Decision: the updater is on. It reads the public GitHub releases of `albertoarena/sudo-hire`, with `v` tags and the `latest` channel. It needs no token. A draft release is not visible to it.
- Decision: the app checks when it starts. NativePHP does this check. `hire:check-updates` runs every hour in the schedule and checks again only when the last check is 24 hours old. The start of the app counts as a check. So GitHub gets at most one check a day, plus one for each start.
- Decision: the updater downloads a new version in the background. It installs it when the user quits the app. It never restarts the app and never installs while the app runs.
- Decision: when a version is downloaded, the app shows a native notification once for each version: "Sudo Hire 1.1.0 is ready. It installs when you quit Sudo Hire." If the notification fails, the next download event tries again.
- Decision: the update check at the start also shows the notification of Electron when the download ends. So the first start after a new release can show two notices. This comes from NativePHP and is not changed here.
- Reason: approved in the M9 brief. The user decides when to quit, so the user decides when the update installs.
- Replaces: none

## 2026-11-03: The release flow

- Milestone: M9a
- Decision: a tag `v*` starts `.github/workflows/release.yml`. The version comes from the tag: `v1.0.0` gives `1.0.0` (`scripts/release-version.mjs`).
- Decision: the workflow runs Pest and Vitest first, then the CA bundle check. One macOS runner then builds the app for arm64 and x64 in one NativePHP build, with PHP 8.4. One build writes one `latest-mac.yml` for both architectures, which the updater needs.
- Decision: on a tag, the build is signed with `CSC_LINK` and `CSC_KEY_PASSWORD` and notarised with `NATIVEPHP_APPLE_ID`, `NATIVEPHP_APPLE_ID_PASS` and `NATIVEPHP_APPLE_TEAM_ID`, all GitHub secrets. The workflow stops when one is missing. It checks the app in each DMG with `codesign --verify --deep --strict`, `spctl -a -vv` and `xcrun stapler validate`. Only then a separate job, the only one with `contents: write`, uploads the DMGs, the zip files and `latest-mac.yml` to a draft release.
- Decision: before the upload, the draft job replaces each space in the file names with a dash, as `latest-mac.yml` does. GitHub would change a space to a dot, and the updater would not find the zip.
- Decision: a human installs and tests the draft on Apple silicon and on Intel, then publishes it. The workflow never publishes.
- Decision: `workflow_dispatch` builds unsigned and keeps the DMGs as workflow artifacts only, with the version `0.0.0-test.<run number>`.
- Decision: every third party action is pinned to a full commit SHA.
- Reason: REQUIREMENTS section 15. NativePHP logs a notarisation error and goes on, so the checks are the gate before the draft.
- Replaces: none

## 2026-11-03: The CA bundle check

- Milestone: M9a
- Decision: `php artisan release:check-cacert` reads the date in the header of the CA bundle of `nativephp/desktop` ("Certificate data from Mozilla as of"). It fails when the bundle is older than 180 days, or has no date. It prints the date. The release workflow runs it before the build.
- Decision: to fix a failure, update `nativephp/desktop` to a version with a newer bundle.
- Reason: "TLS trust comes from the NativePHP CA bundle" says that a release must not ship an old bundle. The file date is not used, because a checkout changes it.
- Replaces: none

## 2026-11-03: The build config

- Milestone: M9a
- Decision: the author is Alberto Arena. The copyright is "Copyright (c) 2026 Alberto Arena. MIT license.", the same as `LICENSE`.
- Decision: the build runs `npm run build` first (`prebuild`). The release build installs Composer packages with `--no-dev`, so the app ships no test tools.
- Decision: the bundled `.env` loses the signing certificate (`CSC_*`), the GitHub tokens (`GITHUB_*`, `GH_TOKEN`), the Apple notarisation keys, and every key that ends in `_PASSWORD`, `_TOKEN` or `_SECRET`. `APP_KEY` stays, because the app needs it. A test runs the NativePHP cleanup on a fake `.env`.
- Decision: `Info.plist` keeps the usage descriptions that Electron and NativePHP put in every app: camera, microphone, audio capture, Bluetooth, Documents and Downloads. Sudo Hire uses none of them. A description gives no access. macOS asks the user only when an app tries to use the device or the folder, and Sudo Hire never tries. The signed app also has no camera or microphone entitlement, so the hardened runtime blocks that access. Electron is installed again after the `prebuild` step, so the build cannot remove its descriptions.
- Reason: approved in the M9 brief. The release workflow writes `.env` from `.env.example`, so it has no secret. The cleanup protects a local build too. A patch of NativePHP and Electron at build time would remove only part of the descriptions (Alberto, 2026-11-03).
- Replaces: none

## 2026-11-04: Child processes get the CA bundle through SSL_CERT_FILE

- Milestone: M9a (follow-up)
- Decision: when a PHP process of the app starts with `curl.cainfo` set, the app sets `SSL_CERT_FILE` to the same bundle (`App\Sources\Support\CaBundle`, called in `AppServiceProvider::boot`). Every process that it starts inherits it.
- Decision: the CA bundle stays the NativePHP bundle, and certificate checks stay on, as in "TLS trust comes from the NativePHP CA bundle".
- Reason: NativePHP passes the bundle only as `-d` flags to the PHP processes that it starts. The scheduler starts `hire:scan` in the background with `PHP_BINARY` and no flags, so the scan used `/etc/ssl/cert.pem` of macOS. That file has no "Sectigo Public Server Authentication Root E46", so every scheduled ECB fetch failed with cURL error 60 in the installed app on 2026-11-04. The job boards worked, because their roots are in that file. The first scan runs in a process that NativePHP starts, so the M9a check did not see it.
- Replaces: none

## 2026-11-05: Apple silicon only, and macOS 12

- Milestone: prep for M9b
- Decision: the app is built only for Apple silicon (arm64). `.github/workflows/release.yml` runs `native:build mac arm64`. There is no Intel build.
- Decision: the app needs macOS 12 (Monterey) or later. Electron 40 and the bundled PHP both need macOS 12.
- Decision: the draft job names the DMG `sudo-hire-<version>-arm64.dmg`. The zip keeps the name in `latest-mac.yml` ("Sudo-Hire-<version>-arm64.zip"), because the updater reads only the zip.
- Decision: a human tests the draft on a Mac with Apple silicon.
- Reason: approved by Alberto (2026-11-04). Nobody can test an Intel build on an Intel Mac before a release. The project does not ship an untested build.
- Replaces: the arm64 and x64 build and the test on Intel in "The release flow" (2026-11-03)

## 2026-11-05: Keywords in the summary notification start with a capital letter

- Milestone: prep for M9b
- Decision: in the summary notification, each keyword starts with a capital letter. The rest stays as the user wrote it: "laravel" gives "Laravel", "PHP" stays "PHP", "php" gives "Php".
- Reason: approved by Alberto (2026-11-04). The settings keep a keyword as the user typed it, so a lowercase keyword gave "4 laravel" while the docs show "4 Laravel".
- Replaces: none

## 2026-11-05: Notarisation with an App Store Connect API key

- Milestone: M9b
- Decision: the release workflow notarises with an App Store Connect API key, not with an Apple ID. electron-builder (26.8.1) does it with `APPLE_API_KEY` (the path of the `.p8` file), `APPLE_API_KEY_ID` and `APPLE_API_ISSUER`. The notarisation of NativePHP (`build/notarize.js`) needs an Apple ID, so it skips.
- Decision: the signing values are in the GitHub environment `release`, which waits for the approval of the maintainer. Secrets: `CSC_LINK`, `CSC_KEY_PASSWORD`, `APPLE_API_KEY` (the `.p8` as base64). Variables: `APPLE_API_KEY_ID`, `APPLE_API_ISSUER`. Only a tag uses the environment. A manual run has no signing values and builds unsigned.
- Decision: the workflow writes the `.p8` to the runner temp folder, readable only by its owner. A last step always removes it, with the `.p12` and the temporary keychain.
- Decision: the API key is a Team Key with Developer access, only for Sudo Hire. It can be revoked alone.
- Decision: the bundled `.env` also loses every `APPLE_*` key.
- Reason: approved by Alberto (2026-11-05). An API key gives no access to the Apple ID, and a change of the Apple ID password does not stop it.
- Replaces: the Apple ID, the app-specific password and the team ID in "The release flow" (2026-11-03)

## 2026-11-05: The signed app and the signed DMG are stapled

- Milestone: M9b
- Decision: electron-builder signs the app, notarises it and staples it before it makes the zip and the DMG. So the app in both files has its ticket. Its stapler call has 3 retries with short pauses (`@electron/notarize`). If all fail, the build fails: run the job again.
- Decision: electron-builder does not sign the DMG, and NativePHP does not let the app change that. A workflow step signs the DMG with the same certificate (from a temporary keychain), notarises it with `notarytool --wait`, and staples it. The step fails when Apple does not accept the DMG, and prints the notarisation log.
- Decision: the step removes the DMG blockmap, because it no longer matches the signed DMG. The DMG entry in `latest-mac.yml` keeps the old checksum. The updater reads only the zip, so this has no effect.
- Decision: every stapler call of the workflow runs through `scripts/retry.sh`: 5 attempts, 15 seconds apart. On macOS runners `xcrun stapler` sometimes fails with error 68 ("The network connection was lost"), after Apple accepted the notarisation.
- Decision: the checks step also checks the DMG itself (`spctl -t open` and `stapler validate`), and not only the app in it.
- Reason: approved in the M9b brief. A stapled ticket lets the first open work with no network.
- Replaces: none

## 2026-11-05: Release candidates and the updater

- Milestone: M9b
- Decision: a tag with a dash, for example `v1.0.0-rc.1`, makes a draft pre-release. The maintainer writes the release notes by hand in the draft. There is no CHANGELOG file.
- Decision: the updater (electron-updater 6.8.3) allows pre-releases only when the installed version has a pre-release part. So an app on `1.0.0-rc.1` finds the newest published `rc` release, for example `1.0.0-rc.2`. It looks for `rc-mac.yml`, does not find it, and reads `latest-mac.yml` of that release. An app on `1.0.0` reads only `/releases/latest`, which has no pre-release.
- Decision: an app on a release candidate does not update to the final release with no dash, for example `1.0.0`. Install the final release by hand over a release candidate.
- Decision: the app has no About item, because it lives in the menu bar. Get Info in Finder shows the name, the version, the copyright and the icon.
- Reason: approved in the M9b brief, from the electron-updater code (`GitHubProvider.getLatestVersion`). The first rc to rc update in M9b confirms it.
- Replaces: none

## 2026-11-13: The workflow makes the signing keychain

- Milestone: M9b
- Decision: a workflow step makes a temporary keychain with a random password, imports the `.p12` from `CSC_LINK` into it, and passes it to electron-builder with `CSC_KEYCHAIN` and `CSC_NAME`. The build step gets no `CSC_LINK`, so electron-builder does not import the certificate itself. The DMG step signs from the same keychain.
- Decision: the step that lists the build fails when there is no DMG, zip or `latest-mac.yml`. `native:build` exits 0 even when electron-builder fails.
- Decision: the build job runs on `macos-15`, not `macos-latest`.
- Reason: the first `v1.0.0-rc.1` run failed. electron-builder 26.8.1 gives the `.p12` password where its keychain password goes (`set-key-partition-list -k`), and macOS refuses it. The build went on with no signed app and no DMG, and the DMG step failed. A fixed runner image cannot change a release without a commit.
- Replaces: the temporary keychain of the DMG step in "The signed app and the signed DMG are stapled" (2026-11-05)

## 2026-11-13: The key ID and the issuer ID are secrets

- Milestone: M9b
- Decision: `APPLE_API_KEY_ID` and `APPLE_API_ISSUER` are secrets in the environment `release`, not variables.
- Reason: approved by Alberto (2026-11-13). The repository is public, so its workflow logs are public too. GitHub masks secrets in a log, but not variables. The two IDs cannot notarise without the `.p8`, but they do not need to be public.
- Replaces: the variables `APPLE_API_KEY_ID` and `APPLE_API_ISSUER` in "Notarisation with an App Store Connect API key" (2026-11-05)

## 2026-11-13: The bootstrap cache stays outside the signed app

- Milestone: M9b
- Decision: when the app runs from its `.app` bundle, `bootstrap/app.php` points `APP_CONFIG_CACHE`, `APP_ROUTES_CACHE` and `APP_EVENTS_CACHE` to `bootstrap/cache` in Application Support (`App\Native\BootstrapCache`). A path that is already set stays. Local development does not change.
- Decision: `packages.php` and `services.php` stay in the bundle. The build writes them, they are part of the signature, and `artisan optimize` does not write them again.
- Decision: the release workflow starts a copy of the built app once. It fails when the app writes no config cache to Application Support, when it writes the cache into its bundle, or, for a tag, when `codesign --verify --deep --strict` fails after the start.
- Reason: NativePHP (2.4.0) runs `artisan optimize` each time the app starts. With no secure bundle it does not set the cache paths, so Laravel wrote three files into the signed app and broke its signature. The installed `v1.0.0-rc.1` showed "a sealed resource is missing or invalid". The app also needed write access to its own folder in Applications.
- Replaces: none

## 2026-11-13: The first run asks macOS for notifications

- Milestone: M9b
- Decision: the last screen of the first run, "First scan", has a Notifications part with "Send a test notification" (`POST /api/app/test-notification`). The first notification of an app makes macOS ask if the app can send notifications, so the question comes while the user looks at the first run.
- Decision: the action answers 204 when the internal API shows the notification, and 502 when it does not. The first run then says where to allow notifications in System Settings. The first run stays four screens.
- Reason: approved by Alberto (2026-11-13), before v1. In the `v1.0.0-rc.1` check, macOS asked with the first notification, the update notice, while nobody looked. The question went by, notifications stayed off, and the update notice did not show. Without notifications the summary, the main part of the app, never shows.
- Replaces: none
