# Changelog

## 0.8.8 - 2026-11-23

- Timer klaxon is a little louder (about +1.6 dB, near the limit before clipping).

## 0.8.7 - 2026-11-23

- Timer klaxon is louder (square-wave horn, about +2 dB) and a little buzzier.

## 0.8.6 - 2026-11-23

- Timer klaxon is louder (compressor with make-up gain, about +1.6 dB).

## 0.8.5 - 2026-11-23

- Timer klaxon is longer (about 4 seconds).

## 0.8.4 - 2026-11-23

- Timer klaxon is louder.

## 0.8.3 - 2026-11-23

- Timer klaxon sounds three times (shorter blasts, still about 3 seconds in all).

## 0.8.2 - 2026-11-23

- Timer alarm is now a submarine dive klaxon (two horn blasts, about 3 seconds).

## 0.8.1 - 2026-11-23

- Timer alarm sounds like an alarm clock and lasts about 3 seconds.

## 0.8.0 - 2026-12-20

- Group generator cards are coloured per group (from the colour scheme).
- Full privacy provider (declares the stored free text), RISK_SPAM on managescores, readonlysession on get_scores, upgrade.txt.
- Name wheel ticks while spinning and chimes when a name is picked; shared, remembered Sound switch (the timer alarm uses it too).

## 0.7.1 - 2026-12-15

- Accessibility: readable text on every wheel wedge (4.5:1), focus trap and Escape in the winner dialog, h2 tool headings, no sideways scroll at 320 px, clearer names for assistive technology, pause switch for the live scoreboard.
- Security hardening: narrower participants query, backup restore limits for the names list, lock on adding scoreboard entries, slower refresh while the board is idle.
- New axe-core accessibility Behat feature.

## 0.7.0 - 2026-12-08

- Added the Scoreboard tool: teachers manage teams or names and points, students watch a live, read-only board.
- New capability `managescores`, new table `classroomtoolbox_scores` (upgrade step), six AJAX web services and a `scoreboard_changed` event.
- Backup, restore and duplication include scoreboard entries.

## 0.6.0 - 2026-12-01

- Added the Group generator, Stopwatch, Timer and Counter tools and a Full screen button.
- New Timer presets setting (`timerpresets` field, upgrade step).
- Existing activities keep only the Name wheel on until their settings are changed.

## 0.5.0 - 2026-11-30

- **Renamed to Classroom Toolbox** (`mod_classroomtoolbox`), a clean new plugin: the Moodle component cannot be renamed in place. The old `mod_namewheel` is left untouched; see `cli/migrate_from_namewheel.php` to move existing wheels across.
- New tab shell with a tool registry: each tool is a tab, switched on per activity in the settings (**Tools** section). The tab bar only appears when more than one tool is on.
- The name wheel is now the first tool (`wheel`); its behaviour is unchanged.
- New `tools` database field; backup and restore include it.
- Tests: new registry, tab-shell and wheel-tool tests; existing tests and Behat features renamed.

Everything below is the history of the plugin as `mod_namewheel`.

## 0.4.4 - 2026-11-29

- Tests: PHPUnit suite and generator (11 tests) and Behat features (8 scenarios).

## 0.4.3 - 2026-11-28

- Wrap the spin in core/pending so automated tests wait for it.

## 0.4.2 - 2026-11-27

- Security hardening: explicit format_string contexts, capability-filtered course index, names-list size limit, sanitised colour scheme on restore.

## 0.4.1 - 2026-11-26

- Security: require `moodle/course:viewparticipants` to list participant names.
- Fix: names with `$` replacement patterns in the removal announcement.
- Privacy: clarified the privacy provider statement.

## 0.4.0 - 2026-11-25

- Added a High contrast colour scheme (WCAG AAA text contrast).
- Docs: added a screenshot to the README.

## 0.3.0 - 2026-11-24

- Added selectable colour schemes (Classic, Ocean, Sunset, Pastel).

## 0.2.1 - 2026-11-23

- Security: respect group visibility when listing groups and members.

## 0.2.0 - 2026-11-23

- Added multi-group selection to combine groups on one wheel; replaced the single-group dropdown.

## 0.1.1 - 2026-11-22

- Security: removed guest access from the view capability; participant names hidden from guests; separate-groups mode restricted to the viewer's own group.

## 0.1.0 - 2026-11-22

- First release: animated spinning name wheel activity, remove-winner option, course participants plus extra names, shuffle/sort/reset, accessibility, backup/restore, privacy provider.
