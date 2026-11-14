# Roadmap

Planned changes after v1.0.0. Nothing here has a date. A change moves to `docs/DECISIONS.md` when it is approved and built.

## Next

- **An About section in Settings.** It shows the app version, for example "Sudo Hire 1.0.1", so the user does not need Get Info in Finder. Today the version is only in Get Info and in the diagnostic report (DECISIONS, "Release candidates and the updater").
- **One update notice, with the words of the app.** Today the update check at the start shows the notice of Electron, "sudo-hire version 1.0.1 has been downloaded and will be automatically installed on exit", next to the notice of the app, so the user gets two notices for one update. Once, in a release candidate, the notice of the app did not appear. The change: start the check without the notice of Electron, so only "Sudo Hire <version> is ready. It installs when you quit Sudo Hire." shows. The name "sudo-hire" also names the data folder, so the fix does not rename the app (DECISIONS, "The updater").
- **Keywords that match on tags need a developer title.** Today a keyword matches the title or the tags, as the source sends them. Some employers tag a job with technologies it does not use: a "Remote Office Assistant" is tagged "php". The change: when a keyword matches only in the tags, the job also needs a title that names a developer role, for example developer, engineer or programmer. A match in the title works as today. This does not stop a developer job with too many tags: a "Senior Data Engineer" tagged with about 50 technologies, "laravel" and "php" among them, still matches.
