# Classroom Toolbox (mod_classroomtoolbox)

A Moodle activity with classroom tools shown as tabs. Teachers add it once and switch on the tools they want.

![The Name wheel tool: a colourful wheel of student names with the names list beside it](docs/screenshot.png)

## Tools

| Tool | Status |
|---|---|
| **Name wheel** | Available |
| Random group generator | Planned (phase 2) |
| Stopwatch | Planned (phase 2) |
| Timers | Planned (phase 2) |
| Counter | Planned (phase 2) |
| Scoreboard | Planned (phase 3) |

### Name wheel

- **Click the wheel** (or the centre button, or press Enter/Space on it) to spin. A random name is chosen with the browser's crypto random source.
- A pop-up announces the winner and offers **Remove from wheel** or **Keep on wheel**.
- The wheel is filled from the course participants (students by default). In group mode, tick any combination of groups to combine them on one wheel. Extra names can be typed in the settings.
- Pick one of five colour schemes (Classic, Ocean, Sunset, Pastel, High contrast).
- The names box beside the wheel can be edited live; **Shuffle**, **Sort A-Z** and **Reset wheel** are available.
- Removing a name only changes the current page; reloading restores the full list. Nothing about the spin is stored, so the plugin holds no personal data.
- Keyboard and screen-reader friendly; respects "reduce motion".

## Install

Copy this folder to `mod/classroomtoolbox` (Moodle 5.0 - 5.3: `public/mod/classroomtoolbox`) and run the Moodle upgrade, or upload the zip under Site administration > Plugins > Install plugins. Add "Classroom Toolbox" to a course as an activity.

### Coming from Name wheel (`mod_namewheel`)

This is a new plugin, so Moodle does not upgrade the old one. Install this plugin next to it, then run (dry run first):

```
php mod/classroomtoolbox/cli/migrate_from_namewheel.php --dry-run
php mod/classroomtoolbox/cli/migrate_from_namewheel.php
```

Back up the site first. After checking the activities, uninstall the old plugin.

## Capabilities

- `mod/classroomtoolbox:addinstance` - add a toolbox (teachers, managers).
- `mod/classroomtoolbox:view` - see the toolbox.
- `mod/classroomtoolbox:appear` - appear as a name on the wheel (students by default).

## Licence

GNU GPL v3 or later.
