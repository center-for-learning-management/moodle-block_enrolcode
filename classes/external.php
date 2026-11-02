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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class external extends external_api {
    public static function delete_parameters(): external_function_parameters {
        return new external_function_parameters([
            'code' => new external_value(PARAM_TEXT, 'the code'),
        ]);
    }

    public static function delete(string $code): string {
        $params = static::validate_parameters(static::delete_parameters(), ['code' => $code]);
        return locallib::delete_code($params['code']) ? '1' : 'permission denied #9iekdir';
    }

    public static function delete_returns(): external_value {
        return new external_value(PARAM_TEXT, 'Return the result.');
    }

    public static function form_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function form(int $courseid): string {
        $params = static::validate_parameters(static::form_parameters(), ['courseid' => $courseid]);
        $context = \context_course::instance($params['courseid']);
        static::validate_context($context);
        // The form lists all existing codes of the course, which would allow anybody to enrol themselves.
        require_capability('enrol/manual:manage', $context);
        return locallib::create_form($params['courseid']);
    }

    public static function form_returns(): external_value {
        return new external_value(PARAM_RAW, 'The form as HTML.');
    }

    public static function get_parameters(): external_function_parameters {
        $date = fn(string $desc) => new external_single_structure([
            'year' => new external_value(PARAM_INT, 'year'),
            'month' => new external_value(PARAM_INT, 'month (1-12)'),
            'day' => new external_value(PARAM_INT, 'day'),
            'hour' => new external_value(PARAM_INT, 'hour'),
            'minute' => new external_value(PARAM_INT, 'minute'),
        ], $desc);
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'id of course'),
            'roleid' => new external_value(PARAM_INT, 'id of role'),
            'groupid' => new external_value(PARAM_INT, 'id of group'),
            'custommaturity' => new external_value(PARAM_INT, '1 if we want to set a custom maturity'),
            'maturity' => $date('the custom maturity as entered in the form'),
            'chkenrolmentend' => new external_value(PARAM_INT, '1 if we want to set an end of enrolment'),
            'enrolmentend' => $date('the end of enrolment as entered in the form'),
        ]);
    }

    public static function get(int $courseid, int $roleid, int $groupid, int $custommaturity, array $maturity, int $chkenrolmentend, array $enrolmentend): string {
        $params = static::validate_parameters(static::get_parameters(), [
            'courseid' => $courseid,
            'roleid' => $roleid,
            'groupid' => $groupid,
            'custommaturity' => $custommaturity,
            'maturity' => $maturity,
            'chkenrolmentend' => $chkenrolmentend,
            'enrolmentend' => $enrolmentend,
        ]);
        $context = \context_course::instance($params['courseid']);
        static::validate_context($context);
        // Without this check a teacher could craft a request for a code with a role they can't assign (e.g. manager)
        // and use it to escalate their own privileges.
        if (!array_key_exists($params['roleid'], get_assignable_roles($context))) {
            throw new \invalid_parameter_exception('roleid is not assignable');
        }

        // The form shows the date in the user's timezone, make_timestamp() converts it the same way.
        $totimestamp = fn(array $d) => make_timestamp($d['year'], $d['month'], $d['day'], $d['hour'], $d['minute']);
        // The date selectors always contain a value, only the checkboxes tell whether it should be used.
        return locallib::create_code(
            $params['courseid'],
            $params['roleid'],
            $params['groupid'],
            $params['custommaturity'] ? $totimestamp($params['maturity']) : 0,
            $params['chkenrolmentend'] ? $totimestamp($params['enrolmentend']) : 0,
        );
    }

    public static function get_returns(): external_value {
        return new external_value(PARAM_ALPHANUM, 'The temporary accesscode.');
    }

    public static function send_parameters(): external_function_parameters {
        return new external_function_parameters([
            'code' => new external_value(PARAM_ALPHANUM, 'The temporary accesscode'),
        ]);
    }

    /**
     * Try enrolment using a temporary accesscode.
     * @return int the courseid if successful, otherwise 0.
     */
    public static function send(string $code): int {
        $params = static::validate_parameters(static::send_parameters(), ['code' => $code]);
        return (int)locallib::enrol_by_code($params['code']);
    }

    public static function send_returns(): external_value {
        return new external_value(PARAM_INT, 'The courseid or 0');
    }
}
