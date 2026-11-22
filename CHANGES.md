# Changes since 0.3.0 (docs only)

- Added `docs/screenshot.png` and showed it in the README. No code changes, so the plugin version is unchanged.

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
