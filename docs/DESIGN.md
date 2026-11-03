---
name: Sudo Hire
description: A macOS menu bar app that stamps every remote job with whether you can take it from where you live.
colors:
  burgundy: "#5E1B2B"
  burgundy-ink: "#F4E8EA"
  page: "#F7F5F2"
  panel: "#FFFFFF"
  sunk: "#EEEBE6"
  ink: "#1E2230"
  ink-2: "#4F5361"
  ink-3: "#62666F"
  rule: "#DCD7D0"
  ok: "#2B6648"
  no: "#A63F2A"
  unk: "#5F636C"
  warn-bg: "#FBEEE9"
  focus: "#8A2E44"
  burgundy-dark: "#6A2133"
  burgundy-ink-dark: "#F6E9EC"
  page-dark: "#1D1B1F"
  panel-dark: "#29262B"
  sunk-dark: "#242126"
  ink-dark: "#ECE8EA"
  ink-2-dark: "#B7B1B6"
  ink-3-dark: "#A49EA4"
  rule-dark: "#3A363C"
  ok-dark: "#7CC29E"
  no-dark: "#EE8B74"
  unk-dark: "#A9A4AA"
  warn-bg-dark: "#3A2522"
  focus-dark: "#E59AAE"
typography:
  window-title:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "17px"
    fontWeight: 600
    lineHeight: 1.3
  pane-title:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.3
  state-title:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.4
  body:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.45
    fontFeature: "\"tnum\""
  job-title:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 500
    lineHeight: 1.35
  job-title-unseen:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 600
    lineHeight: 1.35
  label:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.45
  small:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "11px"
    fontWeight: 600
    lineHeight: 1.3
  stamp-place:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "12px"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "0.08em"
  stamp-word:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"SF Pro Text\", \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "11px"
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: "0.06em"
  mono:
    fontFamily: "ui-monospace, \"SF Mono\", Menlo, Consolas, monospace"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.45
rounded:
  xs: "4px"
  sm: "5px"
  md: "6px"
  track: "7px"
  lg: "8px"
  full: "9999px"
spacing:
  "2xs": "2px"
  xs: "4px"
  sm: "6px"
  md: "8px"
  lg: "10px"
  gutter: "14px"
  xl: "16px"
  pane: "22px"
  indent: "24px"
components:
  strip:
    backgroundColor: "{colors.burgundy}"
    textColor: "{colors.burgundy-ink}"
    typography: "{typography.body}"
    height: "42px"
    padding: "0 14px"
  segmented-track:
    backgroundColor: "{colors.sunk}"
    rounded: "{rounded.track}"
    padding: "2px"
  segmented-item-selected:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "4px 0"
  stamp-admitted:
    textColor: "{colors.ok}"
    rounded: "{rounded.xs}"
    padding: "2px 7px 3px"
  stamp-refused:
    textColor: "{colors.no}"
    rounded: "{rounded.xs}"
    padding: "2px 7px 3px"
  stamp-not-stated:
    textColor: "{colors.unk}"
    rounded: "{rounded.xs}"
    padding: "2px 7px 3px"
  job-row:
    textColor: "{colors.ink}"
    typography: "{typography.job-title}"
    padding: "9px 14px 7px 24px"
  action:
    textColor: "{colors.ink-2}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "3px 6px"
  action-hover:
    backgroundColor: "{colors.sunk}"
    textColor: "{colors.ink}"
  button:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "3px 10px"
  button-primary:
    backgroundColor: "{colors.burgundy}"
    textColor: "{colors.burgundy-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "3px 10px"
  input:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "4px 8px"
  chip:
    backgroundColor: "{colors.sunk}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.xs}"
    padding: "1px 4px 1px 7px"
  sidebar-item:
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "5px 8px"
  sidebar-item-selected:
    backgroundColor: "{colors.burgundy}"
    textColor: "{colors.burgundy-ink}"
  source-warning:
    backgroundColor: "{colors.warn-bg}"
    textColor: "{colors.no}"
    typography: "{typography.label}"
    padding: "7px 14px"
  footer-bar:
    backgroundColor: "{colors.sunk}"
    textColor: "{colors.ink-2}"
    typography: "{typography.label}"
    padding: "6px 8px 7px 14px"
  toast:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.page}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  switch:
    backgroundColor: "{colors.rule}"
    rounded: "{rounded.full}"
    width: "30px"
    height: "18px"
  switch-on:
    backgroundColor: "{colors.burgundy}"
---

# Design System: Sudo Hire

Source of truth for this record: `docs/design/sudo-hire-ui.html` (the approved review page). That page shows each app surface inside a drawn macOS frame. Only the app inside the frames is the system. The review page itself (intro, captions, theme switch, the grey page ground, the drawn window shadow and traffic lights) is not part of it.

Scope and product rules live in `docs/PRODUCT.md` and `docs/REQUIREMENTS.md`. This file is only about how the UI looks and behaves.

## Overview

**Creative North Star: "The Visa Page"**

Every job carries an entry stamp, like a stamp in a passport. The stamp says, for the user's country, if the job admits them. The user reads the stamp before the title. The rest of the app is a quiet visa page: a warm off-white sheet, ink-blue text, thin rules, and native macOS controls. Only the header strip shows the passport cover, in burgundy with a fine guilloche pattern.

The app is dense and calm. A visit takes under a minute, so each job row is short and every action sits on the row. There are no cards, no logos and no tag clouds. Depth comes from tone (page, panel, sunk) and hairline rules, not from shadows.

**Key Characteristics:**
- One signature element: the entry stamp. Nothing else is rotated, ruled in color, or set in tracked capitals. The app icon and the menu bar mark are the same stamp.
- Burgundy is the cover color. It marks the header strip, the primary button, the selected sidebar item, checked controls and the unseen dot.
- Three stamp inks with fixed meanings: green admits, red refuses, grey does not know.
- System font, macOS sizes (11 to 17px), tabular figures everywhere.
- Light and dark appearance follow the Mac. Every token has a dark value.

## Colors

A warm neutral page with one deep accent and three semantic inks.

Implement every color as a CSS custom property on `:root` (`--burgundy`, `--page`, and so on, the frontmatter keys without the `-dark` suffix). Redefine the same names for dark mode under `@media (prefers-color-scheme: dark)`. The `-dark` keys in the frontmatter are those dark values. In Tailwind, map theme colors to the variables (for example `page: 'var(--page)'`), so one class works in both modes.

### Primary
- **Passport Burgundy** (`burgundy`): the header strip, the primary button, the selected settings sidebar item, checked switches, checkboxes, range sliders, and the active first-run step bar. Also the unseen dot in light mode.
- **Cover Ink** (`burgundy-ink`): text and icons on burgundy. About 10:1 on burgundy in both modes.
- **Focus Rose** (`focus`): the keyboard focus ring. In dark mode it also replaces burgundy for the unseen dot, because burgundy on the dark page is only about 1.5:1.

### Stamp inks (semantic)
- **Admitted Green** (`ok`): admitted stamps, healthy source status dots, finished first-scan steps.
- **Refused Red** (`no`): refused stamps, source errors, the footer warning text, the error dot in the sidebar, the stopped-state icon.
- **Unknown Grey** (`unk`): the "Location not stated" stamp.

### Neutral
- **Visa Page** (`page`): the background of the popover, the settings panes and the first-run body.
- **Panel White** (`panel`): controls (inputs, buttons, the selected segment), previews, and the base of the row hover.
- **Sunk Paper** (`sunk`): the footer, the settings sidebar, the segmented control track, chips, notes, and the window title bar.
- **Ink** (`ink`): primary text. **Ink 2** (`ink-2`): secondary text, meta lines, idle actions (7.4:1 on page). **Ink 3** (`ink-3`): tertiary text, hints, table headers, separators (5.6:1 on page).
- **Rule** (`rule`): hairline dividers and borders, the switch track when off, the idle step bars.
- **Warning Wash** (`warn-bg`): the background of the source warning row.

Control borders are not a token. They are `ink` mixed with transparent: 22% for buttons, 25% for inputs and chip fields, 40% on button hover.

**The Three Inks Rule.** Green, red and grey mean admitted, refused and unknown. Use them only for eligibility and for source health. Never use them for decoration, salary or job state.

**The Cover Rule.** Burgundy is the passport cover. It fills surfaces and controls. Do not use it as a text color on the dark page; use `focus` instead.

## Typography

**Font:** the macOS system font (`-apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", Arial, sans-serif`).
**Mono:** `ui-monospace, "SF Mono", Menlo, Consolas, monospace`, for the tagline command line and ATS slugs.

**Character:** native and plain. The type should read like a macOS utility. Tabular figures are on globally (`font-variant-numeric: tabular-nums`) so salaries and times line up.

### Hierarchy
- **Window title** (600, 17px): the heading of each first-run step.
- **Pane title** (600, 15px): settings pane headings.
- **State title** (600, 14px): the heading of empty and stopped states.
- **Body** (400, 13px, line height 1.45): the base size. Settings labels, sidebar items, inputs, the strip name (600).
- **Job title** (500, 13px, line height 1.35): job rows. Unseen jobs use 600. Long titles wrap; use `text-wrap: pretty`.
- **Label** (400 or 500, 12px): meta lines, salaries, actions, buttons, segments, hints, tables, footer, toast.
- **Small** (600, 11px): the menu bar count, the "?" badge, the "also on" badge (400).
- **Stamp place** (700, 12px, tracking 0.08em, uppercase) and **stamp word** (600, 11px, tracking 0.06em, uppercase).

**The 11px Floor Rule.** No text is smaller than 11px. Stamp words and badges sit at the floor; everything else is 12px or larger.

**The Capitals Rule.** Uppercase tracked type belongs to the stamp only. Headings, buttons and labels use sentence case.

## Layout

Fixed macOS window sizes. Spacing uses a small step scale (2, 4, 6, 8, 10, 14, 16, 22, 24px).

- **Popover:** 400 by 640px. A vertical stack of four parts: the strip, the segmented control area, the scrolling list and the footer. The strip is 42px tall. The segmented control area has padding 10px 14px and a hairline below. The scrolling list has flex 1, `overscroll-behavior: contain` and an 8px scrollbar in `rule`.
- **Popover gutter:** 14px left and right. Job rows indent text to 24px on the left so the unseen dot has room at 10px.
- **Settings window:** 720 by 520px, at least 560 by 420px. A 170px sidebar on `sunk` with a hairline right border, then the pane (padding 18px 22px 22px, 16px gap between blocks). Inside the pane, a two-column form: 170px right-aligned labels in `ink-2`, then the control. Row gap 12px, column gap 16px.
- **First-run window:** 520px wide. Body padding 26px 30px 22px, at least 380px tall, 16px gap. A bottom nav bar on `sunk` with a hairline top border (padding 12px 18px).
- **Narrow fallback (under 680px):** the settings sidebar becomes a wrapping row above the pane, and the form becomes one column with left-aligned labels. The fixed app windows do not need this, but keep it if a window can shrink.

Paragraph text in settings and first run stays under about 52ch.

## Elevation & Depth

The app is flat. Depth comes from three tones (`sunk` below, `page` as the sheet, `panel` for controls) and from 0.5px hairlines in `rule`. There are two small exceptions:

- **The selected segment** has a hairline ring and a faint drop (`0 0 0 .5px var(--rule), 0 1px 2px rgba(0,0,0,.06)`), like a native segmented control.
- **The pinned "Location not stated" row** is sticky at the bottom of the list. It casts a soft fade upward over the list (`0 -10px 12px -6px`, the page color at 90%). Rows that scroll under it fade out.
- **The country list** of the combobox floats over the pane like a native menu, with the system menu shadow and a hairline ring. It is a pop-up list, not a surface of the page.

The switch knob has a native-style shadow (`0 1px 2px rgba(0,0,0,.25)`).

The window shadow, the 11px window corners and the traffic lights in the review page are stand-ins for macOS. The real windows get them from the system. Do not draw them in Vue.

**The Paper Rule.** Surfaces are flat paper. If something needs to stand apart, change its tone or add a hairline. Do not add a shadow.

## Shapes

Small, soft corners, like macOS controls. 4px for stamps, chips, badges and the focus ring. 5px for segments and row actions. 6px for buttons, inputs, sidebar items and the toast. 7px for the segmented track. 8px for previews and notes. Dots (unseen, status, sidebar error) are 7px circles. The switch is a pill (30 by 18px).

Lines are 0.5px hairlines everywhere, except the stamp. The stamp border is 1.5px, which makes it the heaviest line in the app.

## Components

### The entry stamp (signature)

A small ruled rectangle on the right of the job title. It answers "can I apply from here?" first.

- **Anatomy:** two centered lines, uppercase. Line 1 is the place (`stamp-place`: the country code like "IT", or a region like "Europe", or "Location"). Line 2 is the verdict (`stamp-word`). Padding 2px 7px 3px, border 1.5px in `currentColor`, radius 4px, line height 1.15. It never shrinks (`flex: none`).
- **Admitted, country:** green ink. "IT / ADMITTED". The user's country is on the job's list.
- **Admitted, region:** green ink. "EUROPE / ADMITTED". The user's region is on the job's list.
- **Admitted, no country:** with no country in the settings, the stamp shows the job's own place, for example "BG / ADMITTED", and "ANYWHERE" only for a worldwide job.
- **Not stated:** grey ink, **dashed** border. "LOCATION / NOT STATED". Only in the "Location not stated" group.
- **Refused:** red ink. The job's own limit, for example "US / ONLY". When the job lists places but not the user's, "NOT / ADMITTED". Refused jobs are filtered out of the list. The refused stamp appears only in previews (settings and first run).
- **Tilt:** each stamp is turned slightly, set through a `--r` custom property (`transform: rotate(var(--r))`). The angle comes from the job id: `tilt(id) = (((id * 37) % 41) - 20) / 10` degrees. That gives a range of -2.0 to +2.0 degrees, varied from row to row but fixed per job. A stamp never changes angle on refresh or between renders. Previews and samples without a job use a fixed angle; the default is -1.5 degrees.
- **Fresh and seen ink:** an unseen job has fresh ink (full opacity). A seen job keeps the stamp but the ink fades. The build fades with `opacity: 0.92`, the strongest fade that keeps every ink at 4.5:1 or more on the light page. The unseen dot and the bold title carry most of the difference.

**The One Stamp Rule.** One stamp per job, on the right of the title. Stamps only carry eligibility. Do not reuse the stamp shape for tags, salary or status.

### Header strip
42px tall, burgundy, with a fine vector guilloche as an inline SVG background. The guilloche is two sets of thin white sine waves (0.55px stroke at 10% and 7% opacity) on a 96 by 42px tile. "Sudo Hire" on the left (13px, 600, tracking 0.01em). The unseen count on the right ("3 unseen", 12px, 92% opacity). Gap 10px, padding 0 14px. In the stopped state the count is absent.

### Segmented control
New / Saved / Applied, each with its count in `ink-3` after a 4px gap. Track on `sunk`, 2px padding, radius 7px. Segments share the width equally, 12px text, `ink-2`. The selected segment is `panel`, `ink`, weight 500, with the hairline ring. Use tab roles (`tablist`, `tab`, `aria-selected`).

### Job row
A grid with 3px gap, padding 9px 14px 7px 24px, a hairline at the bottom. Hover: `panel` at 60% over the page. Lines from top to bottom:

1. **Title and stamp:** the stamp floats on the right, top-aligned, with a 10px gap. The title and the meta line wrap around it, so a short title leaves no gap under it. The meta line breaks only between its parts, and each "·" stays with the part before it.
2. **Meta:** company · via Source · age, 12px `ink-2`, separators "·" in `ink-3`, 6px gap. A job on more than one source adds an "also on <source>" badge (11px, hairline border, radius 4px, padding 0 5px).
3. **Pay:** the original amount, then the converted amount in `ink-2` with "≈", then the "OTE" badge for on-target earnings (styled like "also on"). For example "€5,500 - €7,000 / month" then "≈ €66k - €84k / year". Terms ("Full time, Senior") in `ink-2`. With no salary: a "?" badge (16px square, 1px `ink-3` border, radius 4px, 11px 600), then "Salary not stated", "·", terms, on one line.
4. **Actions:** quiet text actions with a 14px icon (Save, Not for me, Applied), then a spacer, then the Apply button with the external-link icon. Actions start 6px left of the text edge so the icons line up with the text. Apply only opens the apply link in the default browser. It does not move the job to Applied, unlike some job sites: opening a link is not applying. The user presses Applied after applying.

**Row states:**
- **Unseen:** a 7px dot at left 10px, top 15px (`burgundy`, `focus` in dark mode), title weight 600, fresh stamp. Closing the list marks the rows shown as seen; a job that arrives while the list is open stays unseen.
- **Seen:** no dot, title weight 500, faded stamp.
- **Saved:** the Save action shows a filled bookmark and reads "Saved" (`aria-pressed="true"`, `ink`, weight 500). The row moves to the Saved tab. "Not for me" is not offered outside New.
- **Applied:** the Applied action is pressed. The row moves to the Applied tab.
- **Hidden (Not for me):** the row leaves the list. A toast says "Hidden. You will not see this job again" with an Undo link.
- Any action marks the row as seen. Pressing the active state again moves the job back to New ("Moved back to New").

### Toast
Centered near the bottom of the popover (74px above the bottom, above the footer). `ink` background, `page` text, 12px, radius 6px, padding 6px 12px, one line. It rises 8px and fades in (opacity 0.2s, transform 0.25s, `cubic-bezier(.2,.8,.2,1)`), stays 3.2 seconds. Undo is an underlined text button. Use `role="status"` and `aria-live="polite"`.

### Pinned "Location not stated" row
Only on the New tab, after the matches. A full-width button, sticky to the bottom of the list, on `page`, with hairlines above and below and the upward fade. Text "Location not stated: 2 jobs", 12px `ink-2`, a chevron on the right. Expanded (`aria-expanded="true"`): the chevron turns 90 degrees (0.2s), a note follows ("These jobs do not say where you can work from. Check before you apply. They are never in a notification."), then the rows. Opening it scrolls the row to the top of the list, where it sticks, so its jobs show without a scroll. Closing it puts it back at the bottom. These rows have the dashed grey stamp, no unseen dot, and only Save, Not for me and Apply.

### Footer
On `sunk`, hairline on top.
- **Source warning** (only when a source fails): a full-width button on `warn-bg` with red text and a warning icon. It reads "We Work Remotely: certificate error", with "Details" in `ink-2` on the right. It opens the Sources pane.
- **Status bar:** "Last scan 16:05, next 18:05" in `ink-2`, a spacer, then Settings (gear) and Quit (power) as actions in `ink`.

### Empty and stopped states
- **Empty:** centered in the list, padding 44px 28px, 8px gap. A state title ("Nothing new since 16:05") and one line in `ink-2` ("Next scan at 18:05. You get one notification when new jobs match."). Saved and Applied have their own lines ("No saved jobs", "No applications yet"). The strip still shows "0 unseen" and the tabs keep their counts.
- **Stopped:** replaces the tabs, list and footer. Centered: a 30px warning icon in red and the state title "Sudo Hire stopped in the background". Below them: one line in `ink-2` (max 30ch), "Quit Sudo Hire and open it again. Your saved jobs are safe.", and a primary button "Quit Sudo Hire". The web view cannot restart the app when PHP is gone; the button quits through Electron.

### Buttons
- **Default:** `panel`, hairline border (`ink` at 22%), radius 6px, padding 3px 10px, 12px weight 500, optional 14px icon with 6px gap. Hover: border at 40%.
- **Primary:** burgundy fill and border, `burgundy-ink` text. Hover: `brightness(1.12)`. One primary per view (Quit in the stopped state, Next in first run).
- **Actions:** borderless text buttons, 12px `ink-2`, padding 3px 6px, radius 5px, 14px icon with 5px gap. Hover: `sunk` background, `ink` text. Pressed: `ink`, weight 500.

### Inputs and form controls
- **Text field and select:** `panel`, hairline border (`ink` at 25%), radius 6px, padding 4px 8px (selects 24px on the right). Hints below in 12px `ink-3`.
- **Chip field:** a `panel` box with the same border and 4px padding. Chips on `sunk`, radius 4px, 12px. Each chip has a remove button with the close icon (inline SVG at 10px, not a text character), in `ink-3`, padding 2px, radius 3px. Hover: `ink` on `rule`. The button has an `aria-label` ("Remove laravel"). Enter adds a keyword (lowercase).
- **Country combobox:** a text field over the app's whole country table. Focus opens the whole table, with the current country active and scrolled into view, and selects the text. Typing replaces the text and filters by name, or by exact country code; the list then shows up to 40 matches. Arrow keys move, Enter picks, Escape closes, and leaving the field restores the current country. The list is `panel`, radius 8px, 4px padding, at most 220px tall and scrolls. Options are 4px 8px, radius 5px, the name on the left and the code in 12px `ink-3` on the right. The active or hovered option is burgundy with `burgundy-ink` text. Use `role="combobox"` with `aria-expanded`, `aria-autocomplete="list"` and a `listbox` of `option`s. Hint: "Type to search all countries."
- **Checkboxes, sliders:** native, with `accent-color: var(--burgundy)`.
- **Switch:** 30 by 18px pill, `rule` when off, burgundy when on, white 14px knob, 0.15s.
- **Preview box:** `panel`, hairline `rule` border, radius 8px, padding 10px 12px. Shows stamps or a sample notification.
- **Note:** `sunk`, radius 8px, padding 10px 12px, 12px `ink-2`. Used for the ECB rate line and the source status line in Troubleshooting.
- **Plain list:** a bulleted list in `ink-2`, 18px indent, 3px gap. Used in Privacy.
- **Disclosure (FAQ):** a native `details` element with a hairline below and 8px vertical padding. The summary is weight 500; the answer is `ink-2`, under 60ch.
- **Button row:** buttons side by side with an 8px gap, wrapping when narrow.

### Settings window
Window title follows the selected pane. Sidebar items: 13px, 15px icon in `ink-2`, padding 5px 8px, radius 6px. Selected: burgundy fill, `burgundy-ink` text and icon. A pane with a problem shows a 7px red dot on the right of its item (Sources, when a source fails). Each pane opens with a pane title and a 12px `ink-2` subtitle.

Eight panes, in order:
1. **Where you work** (pin): country (the country combobox), a live stamp preview with two real jobs, timezone, work mode, "Location not stated" mode, time difference.
   - **Time difference:** a select with Off, Up to 1 hour, through Up to 6 hours, and Custom. Custom shows a number field (0 to 12) followed by "hours". The filter keeps jobs whose timezone is within plus or minus N hours of the user's. Jobs with no timezone always pass. The hint says so; with Custom it gives an example instead ("Vancouver is 9 hours behind Rome").
2. **Jobs** (search): include and exclude keyword chips, "also match the description", contract, seniority, posted within, hidden companies, hidden jobs.
   - **Posted within:** 24 hours, 3 days, 7 days, 14 days, 30 days. The default is 3 days. Hint: "Jobs are kept for 30 days."
   - **Hidden jobs:** the jobs hidden with "Not for me": title, company and the date hidden, newest first, each with a "Show again" action. The list shows the 10 newest. A "Show all N hidden jobs" action shows the rest in place, with no scroll box inside the pane. "Show again" moves the job back to New. With no hidden jobs: "No hidden jobs." Below the list, only when retention deleted some hidden jobs: a "Clear older hidden jobs (N)" action. It asks for a confirmation in the page, then deletes their tombstones.
3. **Pay** (coin): display currency, minimum per year, payments per year (12, 13, 14), minimum per day, salary not stated. A note with the ECB rate date.
4. **Notifications** (bell): interval slider (1 to 24 hours) and a sample notification.
5. **Sources** (feed): a table with On (switch), Source, Last read, Status. Status is a 7px dot plus text. A green dot shows the job count. A red dot shows the error name, with one plain sentence in `ink-2` below it.
6. **Companies** (building): a table of company, provider plus slug (mono, `ink-3`), last read, and a Remove action. Below: provider select, slug field and an Add button.
7. **Privacy** (lock): subtitle "Everything stays on your Mac. No account, no telemetry." The form has two plain lists. The first list shows what Sudo Hire contacts: the job boards that are on, the career pages the user adds, and the European Central Bank. A hint says that the user's keywords never leave the Mac. The second list shows what is stored on the Mac: jobs for 30 days, settings and companies, exchange rates and a log file. Then a button row: "Show data folder in Finder" and "Read the privacy policy", with the external-link icon. It opens the privacy page of sudohire.dev in the default browser.
8. **Troubleshooting** (help): first a note with the source status line (a red status dot, "We Work Remotely: certificate error at 12:05. Other sources are fine.", and a "Show sources" action that opens Sources). Then four disclosure FAQs: no new jobs for days, a source shows "Certificate error", no notifications, and "Stopped in the background". Then a button row: "Open log folder", "Copy diagnostic report" (reads "Copied" for 1.6 seconds), and the primary "Report a problem". "Report a problem" opens a new GitHub issue on the public repository. A 12px `ink-3` line below describes the report. It has the app version, the macOS version and the status of each source. It has no settings, keywords or jobs.

Tables: 12px, headers weight 500 in `ink-3`, cells padding 8px, hairline rows, top-aligned.

### First run
A 520px window titled "Welcome to Sudo Hire". It opens on start while no country is stored. Four screens, shown by four 22 by 3px step bars (radius 2px) at the bottom left. Bars up to the current step are burgundy, the rest `rule`. Back (hidden on the first screen) and a primary button on the right. Screen readers hear "Step 2 of 4", and each new screen moves the focus to its heading. Nothing is stored before "Run first scan": closing the window early opens the first run again at the next start.

1. **Welcome:** "Jobs you can take, from where you live", one paragraph, the tagline in mono `ink-3`. Button: Start.
2. **Country:** "Where do you work from?", the country combobox (preset from the region of the Mac), a live stamp preview. Button: Next. Next needs a country: with none, the field shows "Choose the country you work from."
3. **Keywords:** "What do you look for?", keyword chips, work mode checkboxes. Button: Run first scan. It saves the country, keywords and work modes in one request. A refused value shows its message on the screen, and the screen stays.
4. **First scan:** a list of the enabled sources, each with a green check and "done", or "reading" or "waiting" in `ink-3`. A source that fails shows its error in `no`. Button: Open the list, which opens the menu bar list and closes the first run.

### Icons
Inline SVG only, on a 16 by 16 view box, with `stroke="currentColor"` and round caps and joins. Icons have no fill, except the filled saved bookmark and the feed dot. Stroke width 1.4 by default; 1.5 for the chevron, the stamp mark and power; 1.6 for the check and the dot of the warning sign. Render sizes:

- 10px for the chip close icon
- 14px in actions, buttons, the warning row and the group row
- 15px in the sidebar
- 30px in the stopped state

The set has 19 icons:

- external link, save, saved (filled), hide (crossed eye)
- check, close, chevron right, warning
- gear, stamp, pin, search, coin, bell
- feed, building, lock, help, power

Draw new icons in the same style.

### App icon and menu bar mark
The concept is "Stamp on cover": the job stamp in light ink on the passport cover.

- **App icon:** the macOS rounded square in burgundy (`burgundy`). It has the same interlaced guilloche as the header strip, with thin white waves at about 7% and 5% opacity. On it, a stamp in `burgundy-ink`: a rounded rectangle outline with a check mark inside, rotated -8 degrees. The build shows it at 128, 64 and 32px.
- **Menu bar mark:** a one-color template icon, as macOS needs. A solid rounded rectangle, tilted -8 degrees, with the check cut out. Unlike the line icons, it is a filled shape. It sits at 15px next to the unseen count (11px, 600). The tilt keeps it from reading as a plain checkbox.

The menu bar mark is in the app since 2026-11-02: `public/menuBarIconTemplate.png` at 15px and its 2x image, rendered from `resources/icons/menu-bar-mark.svg`. The app icon at every macOS size (16 to 1024px) is release work (M9).

### Focus and selection
- **Focus:** every focusable element shows `outline: 2px solid var(--focus)` with a 2px offset and 4px radius, on `:focus-visible` only.
- **Text selection:** burgundy at 30% (`color-mix(in srgb, var(--burgundy) 30%, transparent)`).
- **Selected items:** the selected segment uses `panel` with a ring. The selected sidebar item uses a burgundy fill.

### Motion
Short and functional: the toast (0.2s and 0.25s), the chevron (0.2s), the switch (0.15s). With `prefers-reduced-motion: reduce`, turn transitions off.

## Do's and Don'ts

### Do:
- **Do** put the stamp on every job row, on the right of the title. Use the fixed angle that comes from the job id (-2 to 2 degrees).
- **Do** use the three inks only for eligibility and source health: `ok` admits, `no` refuses or fails, `unk` does not know.
- **Do** show the original salary first and the converted value after it with "≈".
- **Do** show the source on every row as "via <source>" and keep the Apply button with its external-link icon.
- **Do** use 0.5px hairlines in `rule` to separate rows and areas.
- **Do** keep text at 11px or larger, and use 12px or larger for anything but stamp words and badges.
- **Do** define every color as a CSS variable with a dark value, and switch with the system appearance.
- **Do** use the `focus` color, not burgundy, for small burgundy marks on the dark page.

### Don't:
- **Don't** show job board or ATS logos. Names are text only.
- **Don't** use cards, tag clouds or shadows for job rows. A row is text on the page with a hairline.
- **Don't** use uppercase tracked type or rotation for anything but the stamp.
- **Don't** rotate a job stamp more than 2 degrees, or change its angle between renders. Only the icon stamp turns further (-8 degrees).
- **Don't** use more than one primary (burgundy) button in a view.
- **Don't** draw the window frame, shadow or traffic lights in the web view. macOS provides them.

## Open items

- **Final icon drawing:** the app icon at every macOS size, 16 to 1024px (M9).
- **Apply and Applied:** some job sites move a job to Applied when Apply is pressed, so users may expect the same here. A possible answer: after Apply, the toast says "Opened in your browser" with a "Mark as applied" button. Not decided.
- **Keyboard (after v1):** arrow keys move between jobs, Return opens Apply, S saves. The selected row style for keyboard movement is not designed. v1 has Tab order and focus rings.
