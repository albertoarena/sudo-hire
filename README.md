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

## Documentation

- [Requirements](docs/REQUIREMENTS.md)
- [Decisions](docs/DECISIONS.md)
- [Manual QA](docs/QA.md)
