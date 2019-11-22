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
     * @param custommaturity (optional) whether or not user wants a custom maturity.
     * @param maturity (optional) the maturity to set.
     * @return the code that was stored in the database.
     */
    public static function create_code($courseid=0, $roleid=0, $custommaturity=0, $maturity=0) {
        self::clean_db();
        global $COURSE, $DB, $USER;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        if (empty($roleid)) {
            $roleid = 3;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        if (!empty($course->id) && self::is_trainer($courseid)) {
            $enrolcode = (object) array(
                'code' => substr(str_shuffle(str_repeat($x='0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ', ceil(4/strlen($x)) )),1,4),
                'courseid' => $courseid,
                'created' => time(),
                'maturity' => (!empty($custommaturity) && !empty($maturity)) ? $maturity : 0,
                'roleid' => $roleid,
                'userid' => $USER->id,
            );
            // check for duplicate code.
            $chkcode = $DB->get_record('block_enrolcode', array('code' => $enrolcode->code));
            if (!empty($chkcode->id)) {
                // Code already exists - we need another code.
                return self::create_code($courseid, $roleid, $custommaturity, $maturity);
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
        /*
        $context = context_course::instance($courseid);
        $_roles = get_assignable_roles($context);
        $_roleids = array_keys($_roles);
        $roles = array();
        for ($a = 0; $a < count($_roleids); $a++) {
            $roles[$a] = array(
                'id' => $_roleids[$a],
                'name' => $_roles[$_roleids[$a]],
            );
        }
        */
        //return $OUTPUT->render_from_template("block_enrolcode/code_get", array("courseid" => $courseid, "roles" => $roles));
    }
    /**
     * Check if the current user is enrolled in a course.
     * @param courseid (optional) if not given use the id from COURSE
     * @param withcapability (optional) only enrolments with a particular capability.
     */
    public static function is_enrolled($courseid=0, $withcapability = "") {
        global $COURSE, $USER;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $context = context_course::instance($courseid);
        return is_enrolled($context, $USER, $withcapability, true);
    }
    /**
     * Check if the current user is enrolled in a course with capability enrol/manual:manage.
     * @param courseid (optional) if not given use the id from COURSE
     */
    public static function is_trainer($courseid = 0) {
        return self::is_enrolled($courseid, "enrol/manual:manage") || has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Checks if a given code is valid and does the enrolment.
     */
    public static function enrol_by_code($code) {
        self::clean_db();
        global $DB, $USER;

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

                $enrol->enrol_user($instance, $USER->id, $enrolcode->roleid);

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


function block_enrolcode_before_standard_html_head() {
    global $PAGE;
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/user/index.php') > 0) {
        $courseid = optional_param('id', 0, PARAM_INT);
        $PAGE->requires->js_call_amd('block_enrolcode/main', 'injectButton', array($courseid));
    }
    return "";
}
