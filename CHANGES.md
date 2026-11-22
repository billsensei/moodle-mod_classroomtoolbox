# Changes in 0.5.0 (2026-11-30)

Phase 1 of the Classroom Toolbox.

- **Renamed** from Name wheel (`mod_namewheel`) to **Classroom Toolbox** (`mod_classroomtoolbox`). This is a new plugin; the old one is not upgraded in place.
- **Tab shell and tool registry.** Tools are tabs; teachers choose which are on (Settings > Tools). The tab bar is hidden when only one tool is on. Arrow keys, Home and End move between tabs.
- **Name wheel** is the first tool, unchanged. Its settings are grouped under "Name wheel settings" and hidden when the wheel is off.
- New database field `tools`; included in backup and restore.
- `cli/migrate_from_namewheel.php` copies existing Name wheel activities into Classroom Toolbox (`--dry-run` available).
- Tests: registry, shell template and wheel tool (names, guests, groups, permissions) in PHPUnit; Behat features updated, plus a "at least one tool" scenario.
