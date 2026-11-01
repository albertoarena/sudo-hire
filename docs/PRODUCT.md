# Product

<!-- impeccable:product-schema 1 -->

Location: `docs/PRODUCT.md` (committed). The design record for the UI. `docs/REQUIREMENTS.md` stays the authority for scope and rules.

## Platform

web

The UI is a Vue 3 and Tailwind CSS web view inside a NativePHP (Electron) app for macOS. It must feel like a macOS menu bar app: system font, native window behavior, light and dark appearance.

## Users

PHP and Laravel developers in Europe who want a remote job, or a better one. They are often employed and check casually, a few times a day. They do not want to read job boards. They want to know when a job they can actually take appears, and to act on it in seconds.

## Product Purpose

Sudo Hire runs in the macOS menu bar. It reads company career pages (Greenhouse, Lever, Ashby, Personio) and public job boards (LaraJobs, Jobicy, Remotive, Himalayas, We Work Remotely). It keeps only the jobs that match the user's filters. Every few hours it sends one summary notification, for example "6 new jobs: 4 Laravel, 2 PHP".

Success: the user trusts the list. Every job in it accepts people from where they live, at a salary they accept, and nothing important is missing.

## Positioning

"Remote" often does not mean "remote from anywhere". Sudo Hire checks where you can work from, so a developer in Italy does not see a job that is "Remote, US only". It also converts salaries into the user's currency and reads the user's country, contract type and seniority rules. All of it runs on the Mac, with no account and no server.

## Operating Context

- The user is at work or at home, in the middle of something else. A notification arrives, or the menu bar badge shows unseen matches.
- They open the menu bar list and scan the new matches. For each job they decide: apply, save, hide, or mark as applied. Most visits take under a minute.
- Settings are set once on first run (country, keywords, salary) and changed rarely.
- Jobs open in the default browser. Applying happens on the source site, never in the app.

## Capabilities and Constraints

- **List of matches** (REQUIREMENTS section 11): title, company, salary (original and converted, with "≈"), eligibility, source attribution, posted date. Apply opens the original apply link.
- **Job states** (confirmed 2026-11-01):
  - Seen or unseen. Opening the list marks jobs as seen. The menu bar badge counts the unseen ones.
  - Hidden ("Not for me"): never shown again.
  - Saved: kept past the 30-day retention.
  - Applied: moves out of the new list.
  - Hidden, saved and applied need storage that M6a does not have yet.
- **Badges:** "?" for an unknown salary, "OTE" for on-target earnings, "also on" for a job that more than one source lists.
- **"Location not stated" group:** jobs with unknown eligibility, in the default "group" mode, are shown apart from the matches and never notified.
- **Settings:** every filter of REQUIREMENTS section 8 and the notification interval (1 to 24 hours). Also the sources toggle, the ATS company list (`ats:add`), display currency, monthly payments (12, 13, 14) and hours per day. A Privacy pane and a Troubleshooting pane (confirmed 2026-11-01) explain the data and help with problems.
- **Source status:** last success and last error per source. A certificate failure shows as "certificate error".
- **ECB rates:** settings show the rate date and name the ECB as the source. Converted amounts say they are converted.
- **Server stopped:** a clear "stopped in the background" message if the internal PHP server dies.
- Business logic stays in PHP. The Vue frontend shows data and edits settings. It does not filter or parse.
- Undecided: how a click on the notification opens the list (M7). The design is in `docs/DESIGN.md`.

## Brand Commitments

- Name: Sudo Hire. Tagline: `$ sudo hire --remote-from here`, also with another place (`--remote-from italy`). Country names in copy are lowercase in the tagline form.
- Voice: plain English, short sentences, easy for non-native speakers. No em dashes. No uniqueness claims ("the only app that...").
- Source terms are part of the UI: every job shows its source name and keeps its original link and apply link. Jobicy and Himalayas require their link on every apply button. Attribution reads "via <source>".
- No job board or ATS logos. Their names appear as text only.

## Evidence on Hand

- Real job data: trimmed fixtures in `tests/Fixtures/`, and the local database from the M6a QA run (597 jobs, 2026-10-31).
- Real titles, salaries and locations for mock-ups come from these sources. Do not invent companies, jobs or salaries.
- No app icon, menu bar mark, screenshots or testimonials exist yet.

## Product Principles

1. **Trust over volume.** Show fewer jobs the user can take, not more jobs. When the app is unsure, it says so ("?", "Location not stated"), and never guesses.
2. **Seconds, not sessions.** The list is for a quick decision per job. Everything else stays out of the way.
3. **The source stays visible.** Every job shows where it came from and links back to it.
4. **Local and quiet.** No account, no telemetry, one summary notification, nothing when there is nothing new.
5. **Set once.** Settings are complete but calm. Defaults work for a PHP or Laravel developer in Europe.
