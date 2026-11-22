# Name wheel (mod_namewheel)

A Moodle activity that shows an animated spinning wheel of student names, in the style of wheelofnames.com.

![The Name wheel activity: a colourful wheel of student names with the names list beside it](docs/screenshot.png)

- **Click the wheel** (or the centre button, or press Enter/Space on it) to spin. A random name is chosen with the browser's crypto random source.
- A pop-up announces the winner and offers **Remove from wheel** or **Keep on wheel**.
- The wheel is filled from the course participants (students by default). In group mode, tick any combination of groups to combine them on one wheel plus an optional list of extra names typed in the activity settings.
- Pick one of four colour schemes (Classic, Ocean, Sunset, Pastel) in the activity settings.
- The names box beside the wheel can be edited live; **Shuffle**, **Sort A-Z** and **Reset wheel** are available.
- Removing a name only changes the current page; reloading the page restores the full list. Nothing about the spin is stored, so the plugin holds no personal data.
- Keyboard and screen-reader friendly; respects "reduce motion".

## Install

Copy this folder to `mod/namewheel` (Moodle 5.0 - 5.3: `public/mod/namewheel`) and run the Moodle upgrade. Add "Name wheel" to a course as an activity.

## Capabilities

- `mod/namewheel:addinstance` - add a wheel (teachers, managers).
- `mod/namewheel:view` - see the wheel.
- `mod/namewheel:appear` - appear as a name on the wheel (students by default).

## Licence

GNU GPL v3 or later.
