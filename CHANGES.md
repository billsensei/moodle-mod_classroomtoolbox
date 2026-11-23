# Changes in 0.9.1 (2026-11-23)

Fixes from a security and a privacy audit. No database changes (version number bump only).

- **Security**: every scoreboard web service now explicitly requires the `mod/classroomtoolbox:view` capability, on top of `managescores` for the services that change data. A student was already refused, because the activity is hidden from them, but this no longer depends on that. A new test covers a user who holds `managescores` without `view`.
- **Privacy**: deleting all data in an activity context now also empties the activity's list of extra names (which can hold real people's names), not just its scoreboard.
- Tests: 51 PHPUnit tests; the privacy test now checks the names are cleared for that activity only.
