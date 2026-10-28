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
