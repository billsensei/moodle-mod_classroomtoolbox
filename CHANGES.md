# Changes in 0.8.0 (2026-12-20)

- **Group generator**: every generated group now has its own background colour, taken from the activity's colour scheme, with text that stays readable on it (at least 4.5:1).
- **Name wheel sounds**: a soft tick each time a wedge passes the pointer, slowing down with the wheel, and a short chime when a name is picked. Sounds are made in the browser (no sound files).
- **Sound switch** on the wheel. The choice is remembered in the browser and shared with the timer, so you only mute once. The timer's alarm uses the same switch.
- The **Colour scheme** setting now applies to both the wheel and the group cards, and is always shown (the settings section is called "Colours").
- No database changes.
- Tests: Behat checks that the wheel ticks and chimes, that Sound off is silent and remembered, that a finished timer sounds its alarm, and that generated groups have different background colours (using a fake audio context, since a test browser cannot hear).

**Review fixes included in this version (found by running every moodle-dev check)**
- Privacy: the plugin is now a full privacy provider that declares the teacher-typed free text it stores (extra names and scoreboard labels) in the privacy registry, and deleting all data in an activity context clears that activity's scoreboard. Nothing is linked to a user account, so a request about one user still finds nothing.
- Capabilities: `mod/classroomtoolbox:managescores` now carries the `RISK_SPAM` flag (teachers write names and points that everyone sees).
- Performance: `get_scores` is marked `readonlysession`, so the 5-second board refresh no longer holds the PHP session lock.
- Housekeeping: removed an unused language string and an inline style, added `upgrade.txt`, version bumped to 2026122001 (no schema change).

