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
 * Scoreboard storage: entries with a free-text label and points. Entries are never linked to user accounts.
 *
 * All methods are scoped to one toolbox, so an entry id from another toolbox is never touched.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scores {
    /** @var int Most entries one scoreboard can hold. */
    public const MAX_ENTRIES = 100;

    /** @var int Longest label, in characters. */
    public const MAX_LABEL = 100;

    /** @var int Points are kept between minus and plus this value. */
    public const LIMIT = 999999;

    /** @var int Largest change one call may make. */
    public const MAX_DELTA = 1000;

    /**
     * Tidy a typed label: strip tags, collapse white space, trim and shorten.
     *
     * @param string $label Raw label.
     * @return string Possibly empty.
     */
    public static function clean_label(string $label): string {
        $label = trim(preg_replace('/\s+/u', ' ', strip_tags($label)));
        return \core_text::substr($label, 0, self::MAX_LABEL);
    }

    /**
     * The entries, highest points first.
     *
     * @param int $toolboxid Toolbox instance id.
     * @return array[] Each with id, label, score.
     */
    public static function get_all(int $toolboxid): array {
        global $DB;

        $rows = $DB->get_records('classroomtoolbox_scores', ['toolboxid' => $toolboxid], 'score DESC, sortorder ASC, id ASC');
        return array_values(array_map(fn($row) => [
            'id' => (int) $row->id,
            'label' => $row->label,
            'score' => (int) $row->score,
        ], $rows));
    }

    /**
     * One entry of this toolbox.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param int $id Entry id.
     * @return \stdClass
     * @throws \dml_missing_record_exception When the entry is not in this toolbox.
     */
    public static function get_entry(int $toolboxid, int $id): \stdClass {
        global $DB;

        return $DB->get_record('classroomtoolbox_scores', ['id' => $id, 'toolboxid' => $toolboxid], '*', MUST_EXIST);
    }

    /**
     * Add entries with zero points. Empty labels and labels already on the board (ignoring case) are skipped, and
     * adding stops at the size limit.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param string[] $labels Raw labels.
     * @return int How many were added.
     */
    public static function add(int $toolboxid, array $labels): int {
        // One adder at a time per toolbox, so simultaneous requests cannot go past the size limit or add a name twice.
        $lock = \core\lock\lock_config::get_lock_factory('mod_classroomtoolbox')->get_lock('scores_' . $toolboxid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            return self::add_locked($toolboxid, $labels);
        } finally {
            $lock->release();
        }
    }

    /**
     * The work of add(), run while holding the toolbox lock.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param string[] $labels Raw labels.
     * @return int How many were added.
     */
    private static function add_locked(int $toolboxid, array $labels): int {
        global $DB;

        $existing = $DB->get_records(
            'classroomtoolbox_scores',
            ['toolboxid' => $toolboxid],
            'sortorder DESC',
            'id, label, sortorder'
        );
        $seen = [];
        $next = 0;
        foreach ($existing as $row) {
            $seen[\core_text::strtolower($row->label)] = true;
            $next = max($next, (int) $row->sortorder);
        }
        $count = count($existing);
        $added = 0;
        $now = time();
        foreach ($labels as $label) {
            $label = self::clean_label((string) $label);
            $key = \core_text::strtolower($label);
            if ($label === '' || isset($seen[$key]) || $count >= self::MAX_ENTRIES) {
                continue;
            }
            $DB->insert_record('classroomtoolbox_scores', (object) [
                'toolboxid' => $toolboxid,
                'label' => $label,
                'score' => 0,
                'sortorder' => ++$next,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $seen[$key] = true;
            $count++;
            $added++;
        }
        return $added;
    }

    /**
     * Add or subtract points. The change is made in the database, so two teachers clicking at once both count.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param int $id Entry id.
     * @param int $delta Points to add (negative to subtract), within plus or minus MAX_DELTA.
     */
    public static function change(int $toolboxid, int $id, int $delta): void {
        global $DB;

        $entry = self::get_entry($toolboxid, $id);
        $delta = max(-self::MAX_DELTA, min(self::MAX_DELTA, $delta));
        $transaction = $DB->start_delegated_transaction();
        $DB->execute(
            'UPDATE {classroomtoolbox_scores} SET score = score + :delta, timemodified = :now WHERE id = :id',
            ['delta' => $delta, 'now' => time(), 'id' => $entry->id]
        );
        $DB->execute(
            'UPDATE {classroomtoolbox_scores} SET score = :limit WHERE id = :id AND score > :limit2',
            ['limit' => self::LIMIT, 'limit2' => self::LIMIT, 'id' => $entry->id]
        );
        $DB->execute(
            'UPDATE {classroomtoolbox_scores} SET score = :limit WHERE id = :id AND score < :limit2',
            ['limit' => -self::LIMIT, 'limit2' => -self::LIMIT, 'id' => $entry->id]
        );
        $transaction->allow_commit();
    }

    /**
     * Rename an entry and set its points.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param int $id Entry id.
     * @param string $label New label (an empty or duplicate label keeps the old one).
     * @param int $score New points, kept within plus or minus LIMIT.
     */
    public static function update(int $toolboxid, int $id, string $label, int $score): void {
        global $DB;

        $entry = self::get_entry($toolboxid, $id);
        $label = self::clean_label($label);
        if ($label !== '' && \core_text::strtolower($label) !== \core_text::strtolower($entry->label)) {
            $taken = false;
            foreach ($DB->get_records('classroomtoolbox_scores', ['toolboxid' => $toolboxid], '', 'id, label') as $other) {
                if ($other->id != $entry->id && \core_text::strtolower($other->label) === \core_text::strtolower($label)) {
                    $taken = true;
                }
            }
            if (!$taken) {
                $entry->label = $label;
            }
        } else if ($label !== '') {
            $entry->label = $label; // Same name, different capitalisation.
        }
        $entry->score = max(-self::LIMIT, min(self::LIMIT, $score));
        $entry->timemodified = time();
        $DB->update_record('classroomtoolbox_scores', $entry);
    }

    /**
     * Remove one entry.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param int $id Entry id.
     */
    public static function delete(int $toolboxid, int $id): void {
        global $DB;

        $entry = self::get_entry($toolboxid, $id);
        $DB->delete_records('classroomtoolbox_scores', ['id' => $entry->id]);
    }

    /**
     * Set every entry back to zero points.
     *
     * @param int $toolboxid Toolbox instance id.
     */
    public static function reset(int $toolboxid): void {
        global $DB;

        $DB->set_field('classroomtoolbox_scores', 'score', 0, ['toolboxid' => $toolboxid]);
        $DB->set_field('classroomtoolbox_scores', 'timemodified', time(), ['toolboxid' => $toolboxid]);
    }

    /**
     * Remove every entry of a toolbox (used when the activity is deleted).
     *
     * @param int $toolboxid Toolbox instance id.
     */
    public static function delete_all(int $toolboxid): void {
        global $DB;

        $DB->delete_records('classroomtoolbox_scores', ['toolboxid' => $toolboxid]);
    }
}
