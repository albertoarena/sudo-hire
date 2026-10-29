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
- Source terms: not checked: blocked in the cloud session. See `docs/QA.md`, "M3: Source terms and endpoints".
- Checked on: not checked

## 2026-10-29: Source terms of Himalayas

- Milestone: M3
- Decision: the Himalayas source reads the search endpoint every 2 hours, at most 5 pages for each search. The source link is the Himalayas job page (`guid`). The apply link is `applicationLink`, unchanged, with its `utm_source=api` parameter. The attribution is "via Himalayas". `expiryDate` gives `expires_at`.
- Reason: REQUIREMENTS section 4 and the M1 entries say: credit Himalayas, link back, keep the apply link, and remove expired jobs. The source follows these rules. The expiry filter is in M6.
- Source terms: not checked: blocked in the cloud session. See `docs/QA.md`, "M3: Source terms and endpoints".
- Checked on: not checked

## 2026-10-29: Source terms of We Work Remotely

- Milestone: M3
- Decision: the We Work Remotely source reads the back-end category RSS feed every 2 hours. Source and apply links are the We Work Remotely job page. The attribution is "via We Work Remotely".
- Reason: REQUIREMENTS section 4 asks each source milestone to check the endpoint and the terms. The feed has `<ttl>60</ttl>`, so a 2 hour interval is within its own refresh time.
- Source terms: not checked: blocked in the cloud session. See `docs/QA.md`, "M3: Source terms and endpoints".
- Checked on: not checked
