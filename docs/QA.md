# Manual QA

Location: `docs/QA.md` (committed).

Checks that tests cannot cover: menu bar, notifications, windows, timing, permissions, signing. A human runs them on a Mac before merging the pull request that added them.

Add entries in date order. Do not delete entries: mark them passed or failed, with the date.

## Template

```
## Mx: <short title>

- Added: YYYY-MM-DD, branch <branch>
- Why manual: <what tests cannot see>
- Setup: <state before the check, e.g. "fresh install, no settings">
- Steps:
  1. ...
  2. ...
- Expected: <what the human must see>
- Result: not run | passed YYYY-MM-DD | failed YYYY-MM-DD (<short note>)
```

## Before every merge (Mac)

1. Check out the branch.
2. `npm run build`, then `php artisan native:run`.
3. Run every entry for this milestone with result "not run".
4. Update each result.
5. If one fails, send the failure back to the session that made the change.

## Entries
