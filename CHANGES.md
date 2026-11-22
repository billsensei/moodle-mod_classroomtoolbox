# Changes in 0.7.0 (2026-12-08)

Phase 3 of the Classroom Toolbox: the scoreboard.

- **Scoreboard tool**: add teams or names (typed, or copied from the groups or participants), give or take points, set an exact score, rename, remove, reset (with a confirmation). Ranked live with ties sharing a place and an animated re-order; shown full screen on a projector.
- **Students** see a read-only board that refreshes itself every few seconds (only while the page is visible).
- New capability `mod/classroomtoolbox:managescores` (teachers, non-editing teachers, managers).
- New table `classroomtoolbox_scores` (upgrade step included). Entries are free-text labels and points, **not linked to user accounts**; included in backup, restore, duplication and removed with the activity.
- Six AJAX-only web services (`mod_classroomtoolbox_get_scores`, `add_scores`, `change_score`, `update_score`, `delete_score`, `reset_scores`). Each derives the context from the toolbox, checks the capability there, only reaches entries of that toolbox and refuses when the scoreboard tool is off. Point changes are made atomically in the database.
- New event `scoreboard_changed` (what changed, who, when).
- Limits: 100 entries, 100 characters per label, scores within plus or minus 999999, at most 1000 points per change.
- Existing activities do not get the scoreboard automatically; tick it in the activity settings.
- Tests: PHPUnit (storage, every web service incl. permissions, cross-toolbox access and events; backup; privacy; capabilities) and Behat (teacher flow, exact scores and renames, adding groups and participants, remove and confirmed reset, read-only student).
