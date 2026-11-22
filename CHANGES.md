# Changes in 0.4.4 (2026-11-29)

- Added PHPUnit tests (library, colour schemes, privacy provider, view event, backup/restore via duplication) and a data generator. No change to plugin behaviour.

---

# Changes in 0.4.3 (2026-11-28)

- The spin animation is wrapped in `core/pending`, so Behat and other automation wait for the wheel to stop before continuing. No visible change.

---

# Changes in 0.4.2 (2026-11-27)

- Hardening from the security review (no functional change for normal use):
  - Titles, headings and activity names are formatted with an explicit context.
  - The course index only lists wheels the user has `mod/namewheel:view` for.
  - The extra-names box is limited to 1000 names / 50,000 characters.
  - Restore sanitises the colour scheme from the backup file.

---

# Changes in 0.4.1 (2026-11-26)

- Security hardening: participant names are only put on the wheel for viewers who hold `moodle/course:viewparticipants`.
- Fix: a name containing `$&` or similar no longer garbles the screen-reader announcement when it is removed (string replace now uses a function).

- Privacy: clearer privacy statement (extra names are teacher-typed text, not linked to user accounts). No code or database changes.

---

# Changes in 0.4.0 (2026-11-25)

- New **High contrast** colour scheme: dark colours with white text, at least 7:1 contrast (WCAG AAA). No database change.
- Docs: README screenshot.

---

# Changes in 0.3.0 (2026-11-24)

- New **Colour scheme** setting with four palettes: Classic, Ocean, Sunset and Pastel (dark text for readability).
- New database field `colorscheme` (upgrade step included; existing wheels keep the Classic colours). Included in backup and restore.

---

# Changes in 0.2.1 (2026-11-23)

- Security: group lists and memberships now honour each group's visibility setting (members only / own membership only / hidden), using core's `groups_get_all_groups()` with members.

---

# Changes in 0.2.0 (2026-11-23)

- Multi-group selection: in Separate or Visible groups mode, tick any combination of groups (plus "Not in any group" and Select all / Select none) to combine them on one wheel. Filtering happens in the browser, so names already removed stay removed when the selection changes.
- Only members of groups the viewer is allowed to see are sent to the page.
- Replaced the single-group dropdown.

---

# Changes in 0.1.1 (2026-11-22)

- Security: guests no longer have `mod/namewheel:view` by default and never see participant names.
- Security: in separate-groups mode, users without `moodle/site:accessallgroups` only see names from their own visible group (none if they are in no group).

---

# Changes in 0.1.0 (2026-11-22)

First release (since the first version, so this is everything):

- Activity module `mod_namewheel` with an animated canvas spinning wheel.
- Click the wheel to spin; random winner; pop-up with "Remove from wheel" / "Keep on wheel".
- Names from course participants (group-aware) plus extra typed names.
- Live editable names list, shuffle, sort, reset.
- Accessibility: keyboard spin, live-region announcements, reduced-motion support.
- Backup/restore, privacy (null provider), view event and completion-on-view.
