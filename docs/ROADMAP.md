# Roadmap

Planned changes after v1.0.0. Nothing here has a date. A change moves to `docs/DECISIONS.md` when it is approved and built.

## Next

- **An About section in Settings.** It shows the app version, for example "Sudo Hire 1.0.1", so the user does not need Get Info in Finder. Today the version is only in Get Info and in the diagnostic report (DECISIONS, "Release candidates and the updater").
- **One update notice, with the words of the app.** Today the update check at the start shows the notice of Electron, "sudo-hire version 1.0.0-rc.3 has been downloaded and will be automatically installed on exit", and the notice of the app can be lost. The change: start the check without the notice of Electron, so only "Sudo Hire <version> is ready. It installs when you quit Sudo Hire." shows. The name "sudo-hire" also names the data folder, so the fix does not rename the app (DECISIONS, "The updater").
