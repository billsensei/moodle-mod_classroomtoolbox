<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_classroomtoolbox\local;

/**
 * Registry of the tools in the Classroom Toolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toolbox {
    /** @var string Tools switched on for a new activity and when nothing valid is stored. */
    public const DEFAULT_TOOLS = 'wheel';

    /**
     * Tool classes in tab order. Add new tools here.
     *
     * @return string[] Class names implementing tool_interface.
     */
    protected static function classes(): array {
        return [
            tools\wheel::class,
            tools\partners::class,
            tools\groups::class,
            tools\stopwatch::class,
            tools\timer::class,
            tools\counter::class,
            tools\scoreboard::class,
        ];
    }

    /**
     * Ids of all tools that exist, in tab order.
     *
     * @return string[]
     */
    public static function all_ids(): array {
        return array_map(fn(string $class) => $class::get_id(), self::classes());
    }

    /**
     * Turn a stored comma-separated list into known tool ids, in tab order.
     *
     * @param string|null $csv Stored value.
     * @return string[] Never empty: falls back to the default tools.
     */
    public static function parse(?string $csv): array {
        $wanted = array_filter(array_map('trim', explode(',', (string) $csv)));
        $ids = array_values(array_intersect(self::all_ids(), $wanted));
        return $ids ?: explode(',', self::DEFAULT_TOOLS);
    }

    /**
     * Turn tool ids into the value stored in the database.
     *
     * @param string[] $ids Tool ids (unknown ones are dropped).
     * @return string
     */
    public static function serialise(array $ids): string {
        return implode(',', self::parse(implode(',', $ids)));
    }

    /**
     * The tools switched on for an activity.
     *
     * @param \stdClass $record Activity record.
     * @return tool_interface[] Keyed by tool id, in tab order.
     */
    public static function enabled(\stdClass $record): array {
        $enabled = self::parse($record->tools ?? null);
        $tools = [];
        foreach (self::classes() as $class) {
            if (in_array($class::get_id(), $enabled, true)) {
                $tools[$class::get_id()] = new $class();
            }
        }
        return $tools;
    }
}
