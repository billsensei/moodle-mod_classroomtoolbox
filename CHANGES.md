# Changes in 0.9.0 (2026-11-23)

**The toolbox is now a teacher-only tool.** It is meant to be used by the teacher on the classroom screen, not as something students open.

- Students no longer have the `mod/classroomtoolbox:view` capability: they cannot open the toolbox, and the activity is hidden from them on the course page and in the course index. Teachers, non-editing teachers and managers keep it.
- On upgrade, the capability is removed from the student role on existing sites (Site administration > Users > Permissions > Define roles shows this). Anyone can grant it back to a role if they want to.
- "Completion on view" is no longer offered for this activity, because students never open it.
- Fix: on a teacher's scoreboard, clicking the "Pause live updates" switch left it focused, which was treated as typing, so the board never refreshed again after unpausing. Only text and number boxes now count as typing.
- Still kept: the course class list on the wheel and groups, and the saved scoreboard.
- Tests: Behat now runs as a teacher (or a non-editing teacher for the separate-groups check) and has a scenario showing a student cannot open it or see it; PHPUnit checks the capability defaults, the hidden activity and that a student is refused by every scoreboard service.
