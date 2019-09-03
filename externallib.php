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

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/blocks/enrolcode/lib.php");

class block_enrolcode_external extends external_api {
    public static function form_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'id of course'),
        ));
    }

    /**
     * Get form as html.
     * @return created accesscode
     */
    public static function form($courseid) {
        global $PAGE;
        $PAGE->set_context(context_system::instance());
        $params = self::validate_parameters(self::form_parameters(), array('courseid' => $courseid));
        return block_enrolcode_lib::create_form($params['courseid']);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function form_returns() {
        return new external_value(PARAM_RAW, 'The form as HTML.');
    }

    public static function get_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'id of course'),
            'roleid' => new external_value(PARAM_INT, 'id of role'),
        ));
    }

    /**
     * Get a temporary access code.
     * @return created accesscode
     */
    public static function get($courseid, $roleid) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::get_parameters(), array('courseid' => $courseid, 'roleid' => $roleid));
        return block_enrolcode_lib::create_code($params['courseid'], $params['roleid']);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function get_returns() {
        return new external_value(PARAM_TEXT, 'The temporary accesscode.');
    }

    public static function revoke_parameters() {
        return new external_function_parameters(array(
            'code' => new external_value(PARAM_TEXT, 'The temporary accesscode'),
        ));
    }

    /**
     * Revoke a temporary accesscode.
     * @param code the temporary accesscode.
     * @return returns always 1.
     */
    public static function revoke($code) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::revoke_parameters(), array('code' => $code));
        return block_enrolcode_lib::revoke_code($params['code']);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function revoke_returns() {
        return new external_value(PARAM_INT, 'returns always 1');
    }

    public static function send_parameters() {
        return new external_function_parameters(array(
            'code' => new external_value(PARAM_TEXT, 'The temporary accesscode'),
        ));
    }

    /**
     * Try enrolment using a temporary accesscode.
     * @param code the temporary accesscode.
     * @return the courseid if successful, otherwise 0.
     */
    public static function send($code) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::send_parameters(), array('code' => $code));
        return block_enrolcode_lib::enrol_by_code($params['code']);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function send_returns() {
        return new external_value(PARAM_INT, 'The courseid or 0');
    }
}
