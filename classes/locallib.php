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
 * @package    block_enrolcode
 * @copyright  2019 Center for Learning Management (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_enrolcode;

defined('MOODLE_INTERNAL') || die;

class locallib {
    /**
     * Check if the current user has capability enrol/manual:manage.
     * @param courseid (optional) if not given use the id from COURSE
     */
    public static function can_manage($courseid = 0) {
        global $COURSE;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $context = \context_course::instance($courseid);
        return has_capability('enrol/manual:manage', $context);
    }

    /**
     * Removes old entries from database.
     */
    public static function clean_db() {
        global $DB;
        // We remove any mature enrolcodes.
        $params = [time(), self::clean_ts()];
        $select = "(maturity>0 AND maturity<?) OR (maturity=0 AND created<?)";
        $DB->delete_records_select('block_enrolcode', $select, $params);
    }

    /**
     * Returns the timestamp for checking the validity.
     */
    public static function clean_ts() {
        return time() - 60 * 60;
    }

    /**
     * Create a code.
     * @param courseid (optional) the courseid, defaults to COURSE->id.
     * @param roleid (optional) the roleid, defaults to 3 (student).
     * @param groupid (optional) the groupid, defaults to 0.
     * @param maturity (optional) timestamp when the code expires, 0 = valid for one hour only.
     * @param enrolmentend (optional) timestamp when enrolments made with this code end, 0 = never.
     * @param nopermissioncheck (optional) true to suppress permission checks.
     * @return the code that was stored in the database.
     */
    public static function create_code($courseid = 0, $roleid = 0, $groupid = 0, $maturity = 0, $enrolmentend = 0, $nopermissioncheck = false) {
        global $COURSE, $DB, $USER;
        static::clean_db();

        $courseid = $courseid ?: $COURSE->id;
        $roleid = $roleid ?: 3;
        $groupid = (int)$groupid;
        $DB->get_record('course', ['id' => $courseid], 'id', MUST_EXIST);
        if ($groupid && !$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $courseid])) {
            return '';
        }
        if (!$nopermissioncheck && !static::can_manage($courseid)) {
            return '';
        }

        $chars = '0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 7; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while ($DB->record_exists('block_enrolcode', ['code' => $code]));

        $DB->insert_record('block_enrolcode', (object)[
            'code' => $code,
            'courseid' => $courseid,
            'created' => time(),
            'maturity' => (int)$maturity,
            'enrolmentend' => (int)$enrolmentend,
            'roleid' => $roleid,
            'groupid' => $groupid,
            'userid' => $USER->id,
        ]);
        return $code;
    }

    /**
     * Create the form in HTML.
     * @return the form in HTML.
     */
    public static function create_form(int $courseid): string {
        return (new code_form($courseid))->render();
    }

    /**
     * Delete a particular code.
     * @param code the code to delete.
     */
    public static function delete_code($code) {
        global $DB;
        static::clean_db();
        $code = $DB->get_record('block_enrolcode', ['code' => $code]);
        if (!$code) {
            return false;
        }
        if (!self::can_manage($code->courseid)) {
            return false;
        }
        $DB->delete_records('block_enrolcode', ['id' => $code->id]);
        return true;
    }

    /**
     * Check if the current user is enrolled in a course.
     * @param courseid (optional) if not given use the id from COURSE
     * @param withcapability (optional) only enrolments with a particular capability.
     */
    public static function is_enrolled($courseid = 0, $withcapability = "") {
        global $COURSE, $USER;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $context = \context_course::instance($courseid);
        return is_enrolled($context, $USER, $withcapability, true);
    }

    /**
     * Checks if a given code is valid and does the enrolment.
     */
    public static function enrol_by_code($code): int {
        global $CFG, $DB, $SESSION, $USER;
        static::clean_db();

        if (!isloggedin() || isguestuser()) {
            return 0;
        }
        // After a wrong code the next attempt is only allowed after 2 seconds, to slow down guessing codes.
        // Short enough that correcting a typo is not affected.
        if (($SESSION->block_enrolcode_lastfailed ?? 0) > time() - 2) {
            return 0;
        }

        // Codes are case sensitive, but the default MySQL collation is not.
        $enrolcode = $DB->get_record_select('block_enrolcode', $DB->sql_like('code', '?', true), [$code]);
        if (!$enrolcode) {
            $SESSION->block_enrolcode_lastfailed = time();
            return 0;
        }
        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            return 0;
        }

        $course = $DB->get_record('course', ['id' => $enrolcode->courseid], '*', MUST_EXIST);
        $instance = $DB->get_record('enrol', [
            'courseid' => $course->id,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ], '*', IGNORE_MULTIPLE);
        if (!$instance) {
            $instanceid = $enrol->add_default_instance($course) ?? $enrol->add_instance($course);
            $instance = $DB->get_record('enrol', ['id' => $instanceid]);
        }
        $enrol->enrol_user($instance, $USER->id, $enrolcode->roleid, 0, $enrolcode->enrolmentend);

        if ($enrolcode->groupid) {
            require_once($CFG->dirroot . '/group/lib.php');
            groups_add_member($enrolcode->groupid, $USER);
        }
        return $enrolcode->courseid;
    }
}
