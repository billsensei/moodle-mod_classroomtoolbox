# Changes in 0.11.0 (2026-11-23)

- **Timer alarm sounds**: each timer now has an **Alarm sound** dropdown with six choices: Hazard siren (the default, as before), Standard beeps, Alarm clock, Klaxon, Futuristic beeps and Duck quack. A **Try sound** button plays the chosen sound, even when the Sound switch is off. The choice is remembered per timer in the browser. The Sound switch still silences timers that finish. The sounds are made in the browser and were levelled so none is much louder than another (the duck is a few dB quieter on purpose).
- **Partner wheel moved onto the Name wheel tab**: it is no longer a separate tool. The Name wheel has a **Pick partners (two spins per pair)** switch (off each time the page opens). Switching it on or off starts a fresh wheel. Everything else about picking pairs is as in 0.10.0, including the group of three when the class is an odd size.
- On upgrade, an activity that had the separate Partner wheel tool switched on is given the Name wheel instead, so it keeps the feature.
- No database schema changes.
- Fixed while testing: the wheel's start-up data stayed under Moodle's size limit by loading the partner-mode wording only when the switch is first turned on.
- Tests: the timer sound choices (all six, per timer, remembered, plays when a timer ends), partner mode on the Name wheel, the upgrade migration; 52 PHPUnit tests.
