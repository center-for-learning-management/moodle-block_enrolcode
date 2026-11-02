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

/**
 * Public API for other plugins, so they don't depend on the internal file layout of this plugin.
 *
 * @package    block_enrolcode
 * @copyright  2026 Center for Learning Management (http://www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_enrolcode;

class api {
    /**
     * Create a new code without any permission check, the calling plugin is responsible for that.
     *
     * @param int $maturity timestamp when the code expires, 0 = valid for one hour only
     * @param int $enrolmentend timestamp when enrolments made with this code end, 0 = never
     * @return string the code, or '' on failure
     */
    public static function create_code(int $courseid, int $roleid, int $groupid = 0, int $maturity = 0, int $enrolmentend = 0): string {
        return locallib::create_code($courseid, $roleid, $groupid, $maturity, $enrolmentend, true);
    }

    /**
     * Get an existing, still valid code for the given course, role and group.
     */
    public static function get_code(int $courseid, int $roleid, int $groupid = 0): ?string {
        global $DB;

        locallib::clean_db();
        $code = $DB->get_field('block_enrolcode', 'code', [
            'courseid' => $courseid,
            'roleid' => $roleid,
            'groupid' => $groupid,
        ], IGNORE_MULTIPLE);
        return $code ?: null;
    }

    /**
     * URL that enrols the current user with the given code.
     */
    public static function get_enrol_url(string $code): \moodle_url {
        return new \moodle_url('/blocks/enrolcode/enrol.php', ['code' => $code]);
    }

    /**
     * URL of a PNG image with a QR code that points to the enrol URL.
     */
    public static function get_qr_url(string $code): \moodle_url {
        return new \moodle_url('/blocks/enrolcode/qr.php', ['code' => $code]);
    }
}
