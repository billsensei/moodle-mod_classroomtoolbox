# Changes in 0.10.0 (2026-11-23)

- **Partner wheel** (new tool): spin twice to pick a pair of partners. The first spin picks the first person, **Spin for a partner** spins for the second, and **Next pair** takes both off the wheel and adds them to the **Pairs so far** list. **Pick someone else** puts the partner back. When one person is left over, they join the last pair, so a class of an odd size ends with one group of three. It uses the same class list, group tick boxes, colours and Sound switch as the Name wheel, and can be on the same page. Existing activities keep their tools: switch it on under Tools in the activity settings.
- **Settings are remembered** in the browser, per activity, so they survive a reload: the open tab, the ticked groups (wheel, partner wheel and group generator), the group generator's amount and mode, the timers and their lengths, the counters and their names, and the scoreboard's points per click. Results, counter values, running timers and student names are never saved.
- **Start all timers** button in the timer tab: starts every timer that is not already running, at the same moment.
- **Timer alarm** is now a hazard siren, like the alarm in a movie factory: five rising whoops, about 4 seconds, as loud as the horn could get without distorting. The Sound switch still silences it.
- No database changes (version number bump only).
- Tests: new Partner wheel Behat feature and accessibility checks, remembered-settings and Start all timers scenarios, a check that the two wheels do not mix; 51 PHPUnit tests.
