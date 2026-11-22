# Classroom Toolbox (mod_classroomtoolbox)

A Moodle activity with classroom tools shown as tabs. Teachers add it once and switch on the tools they want.

![The Name wheel tool: a colourful wheel of student names with the names list beside it](docs/screenshot.png)

## Tools

Each tool is a tab. Teachers choose which tools are on in the activity settings (Tools). With only one tool on, there is no tab bar. Every tool has a **Full screen** button for projecting it onto a classroom screen.

| Tool | What it does |
|---|---|
| **Name wheel** | Spin a wheel of student names; remove the winner or keep them. |
| **Group generator** | Split the class into random groups, by number of groups or people per group. |
| **Stopwatch** | Start, stop, lap and reset, with a lap list. |
| **Timer** | Countdown timers with preset buttons, a sound and a flash at the end, and up to four timers at once. |
| **Counter** | Up to eight named counters with plus, minus and reset. |
| Scoreboard | Planned (phase 3) |

![The Name wheel tool with the tab bar](docs/screenshot.png)

### Name wheel

- **Click the wheel** (or the centre button, or press Enter/Space on it) to spin. A random name is chosen with the browser's crypto random source.
- A pop-up announces the winner and offers **Remove from wheel** or **Keep on wheel**.
- The wheel is filled from the course participants (students by default). In group mode, tick any combination of groups to combine them on one wheel. Extra names can be typed in the settings.
- Pick one of five colour schemes (Classic, Ocean, Sunset, Pastel, High contrast).
- The names box beside the wheel can be edited live; **Shuffle**, **Sort A-Z** and **Reset wheel** are available.
- Removing a name only changes the current page; reloading restores the full list.

### Group generator

![The group generator after making three groups](docs/groups.png)

- Starts from the same people as the wheel (course participants you are allowed to see, plus typed names); edit the list freely.
- Choose **Number of groups** or **People per group**, then **Generate groups**. Groups differ in size by at most one person, and the shuffle uses the browser's crypto random source.
- In group mode, tick which Moodle groups to include.
- **Copy groups** puts the result on the clipboard as plain text.

### Stopwatch

Start, stop, **Lap** and reset. Time comes from timestamps, so it stays accurate if the tab is in the background.

### Timer

![Two timers side by side](docs/timer.png)

- Preset buttons (set per activity, default 1, 3, 5, 10 minutes), or type minutes and seconds.
- A beep (switch it off with **Sound**) and a flashing card when time is up; the end is also announced to screen readers.
- Up to four timers at once. The browser is asked to keep the screen on while a timer runs.

### Counter

Up to eight counters. Rename each one, then raise, lower or reset it.

### Privacy

Nothing a tool does is stored: spins, groups, laps, timers and counts live only in the open page. The plugin holds no personal data.

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
