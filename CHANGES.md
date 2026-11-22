# Changes in 0.7.1 (2026-12-15)

Security and accessibility review fixes. No database changes.

**Accessibility**
- **Wheel text contrast (WCAG 1.4.3):** each wedge now gets white or dark text, whichever reads better, and a few wedge colours were nudged so every wedge of every scheme gives at least 4.5:1 (the default Classic scheme had wedges as low as 1.9:1). The High contrast scheme still gives 7:1.
- **Winner dialog:** Tab and Shift+Tab stay inside it, Escape closes it wherever focus is, the rest of the page is made inert while it is open, and its name now reads "The winner is ...".
- **Headings:** tool headings are h2 (the page title is h1), so no heading level is skipped.
- **Narrow screens:** the wheel and group generator no longer scroll sideways at 320 px width.
- **Names for assistive technology:** the scoreboard rank is read as "Place N"; each name field says which place it is; each timer's presets, minutes and seconds fields and buttons say which timer they belong to.
- **Pause live updates** switch on the scoreboard (WCAG 2.2.2).

**Security hardening**
- The participants query loads only the name columns, never e-mail addresses or password hashes.
- Restoring a backup applies the same limit to the extra names (1000 names, 50000 characters) as the settings form.
- Adding scoreboard entries takes a lock per toolbox, so simultaneous requests cannot exceed the 100-entry limit or add a name twice.
- The scoreboard refresh backs off from every 5 seconds to every 30 seconds while nothing changes, and checks again straight away when the tab becomes visible.

**Tests**
- PHPUnit: contrast of every wedge in every scheme, names limits, lock release, display-name formats, restore limits, no skipped heading level.
- Behat: keyboard focus and Escape in the winner dialog, inert background, pausing live updates, and a new `accessibility.feature` that runs axe-core over every tool for a teacher and a student.
