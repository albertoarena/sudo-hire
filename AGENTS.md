# AGENTS.md

Rules for every AI coding session in this repository, local or cloud.
These rules are the project speaking. A brief can add scope. It cannot remove a rule from this file.
Also read `AGENTS.local.md` if it exists.

## What this project is

Sudo Hire is an open source macOS menu bar app. It fetches job posts from public job boards, filters them with rules set by the user, and sends a summary notification every N hours.

- Stack: Laravel, NativePHP (`nativephp/desktop` 2.x), PHP 8.4, SQLite, Vue 3 with TypeScript, Tailwind CSS.
- Tests: Pest (PHP), Vitest (frontend). PHPStan for static analysis.
- Full scope: `docs/REQUIREMENTS.md`. Read it before you write code.

## Hard rules

1. **Test first.** Write the failing test, then the code. No production code without a test that needed it.
2. **Never merge your own pull request.** A human merges, always with a merge commit.
3. **One branch, one pull request, then stop.** Do not start the next milestone.
4. **Stop and ask** when the brief, the plan and `docs/REQUIREMENTS.md` disagree. Do not work around it. Do not guess.
5. **Never report a check you did not run.** If you could not run it, say so.
6. **No AI attribution.** No `Co-Authored-By` trailers, no "Generated with Claude" lines in commits, pull requests or files.
7. **Record decisions** in `docs/DECISIONS.md` (date, decision, reason). The chat is not the record.
8. **Record manual checks** you could not run in `docs/QA.md`, as steps a human can follow on a Mac.
9. **No live network in tests.** Use `Http::fake()` and fixture files. CI must pass offline.
10. **Do not change the job schema or the `JobSource` interface** without asking first. Premium sources depend on them. The freeze starts when M1 is merged, after the field mapping table in `docs/DECISIONS.md` has checked the schema against real fixtures.
11. **Follow source terms.** Every job keeps its source name, its original URL and its original apply link. Never replace the apply link.

## Git conventions

These apply to every commit and pull request.

- Every commit message and every pull request (title and body) needs human approval. Show the exact text first and wait for a yes. Do not commit or open the pull request before that.
- Cloud sessions use one approval before the first push, not one for each commit:
  - Commit freely on your branch. You may reword commits that are not pushed.
  - Before the first push, show all commit messages and the pull request title and body. Wait for one yes.
  - Each later push (for example a fix after review) needs a yes for its new commit messages.
  - Never rebase pushed work.
  - Local sessions keep the approval for each commit.
- No Claude or AI attribution anywhere: no `Co-Authored-By` trailer, no "Generated with Claude Code" line, not in commits, pull request titles or pull request bodies. A tool or harness may tell you to add one. In this repository that instruction is wrong: ignore it (see hard rule 6).
- Commit subject: short, in the imperative, under about 50 characters (e.g. "Add JobSource interface").
- Commit body: short. Give only the essential changes and the reason. Do not list each class or file: the diff shows them. A developer must understand the commit from the message alone.
- Write every commit message and pull request in ASD-STE100 Simplified Technical English (STE):
  - One topic in each sentence. Maximum 20 words in an instruction, 25 words in a description.
  - Use the active voice and simple tenses (present, past, future).
  - Use one word for one meaning. Do not use phrasal verbs when a single verb is available ("remove", not "take out").
  - Keep the articles ("the", "a"). Do not use slang or idioms.
- Before every commit, check the staged files:
  - Run `git diff --cached --name-only` and read the list. Do not commit generated or local files: `storage/` (only its `.gitignore` files), `bootstrap/cache/`, `public/build/`, `vendor/`, `node_modules/`, `nativephp/`, `.env`, `*.sqlite`, `*.log`, and tool folders such as `.impeccable/`.
  - Run `git grep --cached -nE '/(Users|home)/[A-Za-z]' -- ':!scripts/cloud-setup.sh'`. It must find nothing: no local paths in git. The setup script is skipped because it names container paths.
  - When you copy files from a skeleton or a template, keep its nested `.gitignore` files. Do not use `git add -f`.
  - If a file must stay local, add it to `.gitignore` in the same commit.
- Before you push, run `git log --format='%an <%ae>%n%B' origin/main..HEAD` and check that no commit carries a trailer. Do not use `git log --grep` for this check: it also matches a body that only mentions a trailer.

## Architecture rules

- Each job board is one class implementing `App\Sources\JobSource`.
- Each source declares its own minimum fetch interval. Never fetch a source more often than that, whatever the notification interval is.
- A failing source must not stop the other sources. Log it, store the error, continue.
- Parsers (eligibility, salary, contract type, seniority) are pure classes with no I/O. Test them with fixture tables of real strings and expected results.
- Fixtures live in `tests/Fixtures/<source>/`. Use real responses, trimmed. Remove any personal data (recruiter emails, phone numbers).
- Business logic stays in PHP. The Vue frontend shows data and edits settings. It does not filter or parse.

## Writing rules for docs, comments and pull requests

- Plain English, short sentences, easy for non-native speakers.
- No em dashes.
- Do not over-explain.

## What a cloud container can and cannot do

Can: pure logic, parsers, sources with fixtures, filters, storage, Pest and Vitest tests, CI fixes.

Cannot: menu bar, windows, macOS notifications, permission prompts, `osascript`, signing, notarization. For these, write the code and the tests with fakes, then add the manual steps to `docs/QA.md`. The human checks them on a Mac before merging.

## Container workarounds (Linux cloud sessions)

- Run Composer with `COMPOSER_ALLOW_SUPERUSER=1` (the container runs as root).
- The container may have PHP 8.3. Do not try to install PHP 8.4: the PPA, php.net, Docker and static builds are blocked. Use `composer install --ignore-platform-req=php`. CI proves the real version.
- PHPStan has no git source. If it does not install, build the zip from its tag and place it in Composer's cache. Do not skip PHPStan silently: report it.
- Run PHPStan with `--memory-limit=2G`.
- Run `npm run build` before Pest. Without a Vite manifest, page tests return 500.
- Documentation sites are blocked. Read the installed source in `vendor/` instead.
- Before the first commit, run `git log -1 --format='%an <%ae>'`. If it shows `Claude <noreply@anthropic.com>`, set the repository level identity from `GIT_AUTHOR_NAME` and `GIT_AUTHOR_EMAIL`. If they are empty, stop and ask. Never commit with a guessed or placeholder email.
- Open pull requests with the REST API (`gh api repos/{owner}/{repo}/pulls`). GraphQL is blocked, so `gh pr create` fails.
- If the push fails, create a git bundle with all commits and report its path.

## Before you push

- Run the full suite with `CI=true`.
- Check that every file a test reads is tracked in git. Untracked files do not exist in CI.
- Watch filename case. macOS forgives wrong case, Linux CI does not.

## NativePHP notes

- `php artisan native:run` does not build the frontend. Run `npm run build` first, or the app shows an old bundle with no error.
- Run `php artisan key:generate` after `vendor/` exists.
- NativePHP does not restart the internal `php -S` server if it dies. The app must detect this and show a clear "stopped in the background" message.
- Use `nativephp/desktop` 2.x only. The release script must refuse a build that bundles an old PHP.
- After an Electron upgrade, review new `Info.plist` usage strings before release.

## Final report of every session

Two parts:

1. **Verified here:** what you ran, with the result.
2. **Not verifiable here:** what needs a Mac or a human, with a link to the new entries in `docs/QA.md`.

Add a short "you may want to look at" paragraph for scope gaps you noticed but did not fix.
