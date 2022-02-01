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

defined('MOODLE_INTERNAL') || die;

class block_enrolcode_lib {
    public static $create_form_courseid = 0;

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
        $sql = "DELETE FROM {block_enrolcode}
                    WHERE (maturity>0 AND maturity<?)
                        OR (maturity=0 AND created<?)";
        $DB->execute($sql, array(time(), self::clean_ts()));
    }
    /**
     * Returns the timestamp for checking the validity.
     */
    public static function clean_ts() {
        return time() - 60*60;
    }
    /**
     * Create a code.
     * @param courseid (optional) the courseid, defaults to COURSE->id.
     * @param roleid (optional) the roleid, defaults to 3 (student).
     * @param groupid (optional) the groupid, defaults to 0.
     * @param custommaturity (optional) whether or not user wants a custom maturity.
     * @param maturity (optional) the maturity to set.
     * @param chkenrolmentend (optional) if an end of enrolment shall be set.
     * @param enrolmentend (optional) the timestamp when enrolments shall end.
     * @param nopermissioncheck (optional) true to suppress permission checks.
     * @return the code that was stored in the database.
     */
    public static function create_code($courseid = 0, $roleid = 0, $groupid = 0, $custommaturity = 0, $maturity = 0, $chkenrolmentend = 0, $enrolmentend = 0, $nopermissioncheck = false) {
        self::clean_db();
        global $COURSE, $DB, $USER;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        if (empty($roleid)) {
            $roleid = 3;
        }
        $groupid = intval($groupid);
        if (!empty($groupid)) {
            // Check if that group exists and belongs to the course!
            $group = $DB->get_record('groups', array('id' => $groupid));
            if (empty($group->id) || $group->courseid != $courseid) {
                return '';
            }
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        if (!empty($course->id) && ($nopermissioncheck || self::can_manage($courseid))) {
            $codelength = rand(4,7);
            $enrolcode = (object) array(
                'code' => substr(str_shuffle(str_repeat($x='0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ', ceil(4/strlen($x)) )),1,$codelength),
                'courseid' => $courseid,
                'created' => time(),
                'maturity' => (!empty($custommaturity) && !empty($maturity)) ? $maturity : 0,
                'enrolmentend' => (!empty($chkenrolmentend) && !empty($enrolmentend)) ? $enrolmentend : 0,
                'roleid' => $roleid,
                'groupid' => $groupid,
                'userid' => $USER->id,
            );
            // check for duplicate code.
            $chkcode = $DB->get_record('block_enrolcode', array('code' => $enrolcode->code));
            if (!empty($chkcode->id)) {
                // Code already exists - we need another code.
                return self::create_code($courseid, $roleid, $groupid, $custommaturity, $maturity);
            } else {
                // We can store that code.
                $id = $DB->insert_record('block_enrolcode', $enrolcode, true);
                if (!empty($id) && $id > 0) {
                    return $enrolcode->code;
                } else {
                    return '';
                }
            }
        } else {
            return '';
        }
    }
    /**
     * Create the form in HTML.
     * @param courseid the courseid the form is built for.
     * @return the form in HTML.
     */
    public static function create_form($courseid) {
        self::$create_form_courseid = $courseid;
        require_once(__DIR__ . '/classes/code_form.php');
        $codeform = new code_form(null, null, 'post', '_self', array('class' => 'ui-enrolcode'), true);
        return $codeform->render();

        global $OUTPUT;
    }

    /**
     * Delete a particular code.
     * @param code the code to delete.
     */
    public static function delete_code($code) {
        global $COURSE, $DB, $USER;

        self::clean_db();

        if (self::can_manage($COURSE->id)) {
            return ($DB->delete_records('block_enrolcode', [ 'code' => $code ]) ? 1 : 'error');
        } else return 'permission denied';
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
    public static function enrol_by_code($code) {
        self::clean_db();
        global $CFG, $DB, $USER;

        if(!isloggedin() || isguestuser($USER)) {
            return 0;
        } else {
            $enrolcode = $DB->get_record('block_enrolcode', array('code' => $code));
            if (!empty($enrolcode->id)) {
                // Code is valid.
                $course = $DB->get_record('course', array('id' => $enrolcode->courseid), '*', MUST_EXIST);
                $context = context_course::instance($course->id);

                $enrol = enrol_get_plugin('manual');
                if ($enrol === null) {
                    return false;
                }
                $instances = enrol_get_instances($course->id, true);
                $manualinstance = null;
                foreach ($instances as $instance) {
                    if ($instance->enrol == 'manual') {
                        $manualinstance = $instance;
                        break;
                    }
                }

                if (empty($manualinstance->id)) {
                    $instanceid = $enrol->add_default_instance($course);
                    if ($instanceid === null) {
                        $instanceid = $enrol->add_instance($course);
                    }
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                }

                $enrol->enrol_user($instance, $USER->id, $enrolcode->roleid, 0, $enrolcode->enrolmentend);

                if (!empty($enrolcode->groupid)) {
                    // Add user to the usergroup in the course.
                    require_once($CFG->dirroot . '/group/lib.php');
                    groups_add_member($enrolcode->groupid, $USER);
                }

                return $enrolcode->courseid;
            } else {
                return 0;
            }
        }
    }
    /**
     * Revokes a given code.
     */
    public static function revoke_code($code) {
        self::clean_db();
        global $DB, $USER;

        $enrolcode = $DB->get_record('block_enrolcode', array('code' => $code));

        if (empty($enrolcode->maturity)) {
            // We only revoke manually if we have no maturity.
            $DB->delete_records('block_enrolcode', array('code' => $code));
        }
    }
}
