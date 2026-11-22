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
