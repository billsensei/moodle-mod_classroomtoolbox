# Changes in 0.12.2 (2026-11-23)

- **One button spins both wheels.** With two wheels there is now a single **Spin both wheels** button below them (clicking either wheel does the same, and the buttons in the middle of the wheels are hidden). Wheel 1 starts spinning, and Wheel 2 starts by itself half a second later. Back to one wheel, the normal Spin button returns.
- **Both winners appear together.** When both wheels have stopped, one dialog shows "The winners are" and both names joined by "and", for example "Guest Speaker and Eve Evans" (the first from Wheel 1, the second from Wheel 2). **Remove both from wheels** takes one name off each wheel; **Keep both on wheels** leaves everything. Nothing is removed automatically. If either wheel has no names, nothing spins and a message says so.
- This replaces 0.12.1's behaviour of spinning the two wheels independently.
- **Privacy wording corrected**: the README said remembered settings never include student names, but counter names are saved exactly as the teacher typed them in the browser's own storage (never on the server), so they could hold a name. The README now says so and suggests neutral counter names on a shared computer. The class list and the names on the wheels are still never saved.
- No database changes (version number bump only).
- Tests: scenarios for the single button, the half-second gap between the wheels, the combined dialog, Keep both and Remove both, clicking a wheel and the empty-wheel message; steps that measure when each wheel starts; 52 PHPUnit tests.
