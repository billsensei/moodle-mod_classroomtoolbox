# Changes in 0.6.0 (2026-12-01)

Phase 2 of the Classroom Toolbox: four new tools, all running in the browser.

- **Group generator**: split the class into a number of groups or groups of a size; sizes differ by at most one; optional Moodle group filter; copy to clipboard.
- **Stopwatch**: start, stop, lap, reset; timestamp based.
- **Timer**: countdown with presets, custom minutes and seconds, sound and flash at the end, up to four timers, screen wake lock while running.
- **Counter**: up to eight named counters.
- **Full screen** button on the shell, for the projector.
- New setting **Timer presets (minutes)** (database field `timerpresets`, upgrade step included).
- Settings page: the names settings are now shared by the wheel and the group generator; the colour scheme only shows for the wheel.
- Existing activities keep only the Name wheel switched on; open the activity settings and tick the new tools. New activities have every tool ticked.
- Code: participant gathering moved to `\mod_classroomtoolbox\local\participants`, shared by the wheel and the group generator.
- Tests: PHPUnit (registry order, every tool renders, presets cleaning, participants) and Behat features for each tool.
