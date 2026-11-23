# Changes in 0.12.0 (2026-11-23)

- **Second wheel**: the Name wheel tab has a new **Add second wheel** button. The second wheel appears beside the first and the names are split between the two at random (with an odd number, one wheel, chosen at random, gets the extra name). Spin either wheel first, and the other whenever you are ready; each wheel is headed Wheel 1 or Wheel 2 and the winner dialog says which wheel it came from.
- **Nothing is removed automatically.** After each spin the teacher chooses **Remove from wheel** or **Keep on wheel**, as on the normal wheel. A "Picked: ..." line under each wheel shows its last pick, so the pair is easy to see.
- **Split names again** mixes everyone still on a wheel at random, and **Remove second wheel** puts all the names back on one wheel. Each wheel has its own names box; **Reset wheel** and the group tick boxes work across both wheels. Only one wheel can spin at a time.
- The second wheel is off each time the page opens, and nothing about it is saved.
- **Removed**: the "Pick partners (two spins per pair)" switch from 0.11.0, with its "Pairs so far" list and the group-of-three rule. The second wheel replaces it.
- No database changes (version number bump only).
- Tests: 7 new second-wheel scenarios, accessibility checks of the two-wheel page and the second wheel's dialog, and a step that checks the names are shared between the two wheels; 52 PHPUnit tests.
