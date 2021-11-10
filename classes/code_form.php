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
global $CFG;
require_once($CFG->libdir . "/formslib.php");
require_once($CFG->dirroot . "/blocks/enrolcode/locallib.php");

class code_form extends moodleform {
    static $accepted_types = '';
    static $areamaxbytes = 10485760;
    static $maxbytes = 1024*1024;
    static $maxfiles = 1;
    static $subdirs = 0;

    function definition() {
        global $DB;
        $courseid = block_enrolcode_lib::$create_form_courseid;

        $uniqid = ceil(time() / rand(0, 99999));

        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $context = context_course::instance($courseid);
        $roles = get_assignable_roles($context);

        $sql = "SELECT roleid id,name
                    FROM {role_names}
                    WHERE contextid = ?";
        $overwrites = $DB->get_records_sql($sql, [ $context->id ]);

        foreach($roles as $roleid => $role) {
            if (!empty($overwrites[$roleid]->name)) {
                $roles[$roleid] = $overwrites[$roleid]->name;
            }
        }
        asort($roles);

        $mform->addElement('html', "<p>" . get_string('role') . "</p>");
        $mform->addElement('select', 'roleid', get_string('role'), $roles);

        $_groups = $DB->get_records_sql("SELECT id,name FROM {groups} WHERE courseid=? ORDER BY name ASC", array($courseid));
        if (count($_groups) > 0) {
            $groups = array();
            $groups[0] = get_string('none');
            foreach ($_groups AS $group) {
                $groups[$group->id] = $group->name;
            }
            $mform->addElement('html', "<p>" . get_string('group') . "</p>");
            $mform->addElement('select', 'groupid', get_string('group'), $groups);
        } else {
            $mform->addElement('hidden', 'groupid');
        }

        $onclick = 'require(["jquery"], function($) { var inp = $("[data-uniqid=\'custommaturity-' . $uniqid . '\']"); inp.closest("form").find("#id_maturity_day").closest(".row.fitem").css("display", $(inp).is(":checked") ? "block" : "none"); });';
        $mform->addElement('checkbox', 'custommaturity', get_string('custommaturity', 'block_enrolcode'), NULL, array('data-uniqid' => 'custommaturity-' . $uniqid, 'onclick' => $onclick));
        $mform->setType('custommaturity', PARAM_INT);

        $utime = new DateTime("now", core_date::get_user_timezone_object());
        $utz = $utime->getTimezone();
        $startendargs = array(
               'startyear' => date("Y"),
               'stopyear' => date("Y") + 5,
               'timezone' => floor($utz->getOffset(new DateTime("now")) / 60 / 60),
               'step' => 5,
               'optional' => 0,
            );
        $mform->addElement('date_time_selector', 'maturity', get_string('maturity', 'block_enrolcode'), $startendargs);

        $mform->addElement('html', "<a href=\"#\" class=\"btn btn-secondary\" onclick=\"var btn = this; require(['block_enrolcode/main'], function(MAIN) { MAIN.getCode(btn); }); return false;\">" . get_string('create') . "</a>");

        $oldcodes = $DB->get_records_sql("SELECT * FROM {block_enrolcode} WHERE courseid=? ORDER BY maturity ASC", array($courseid));
        if (count($oldcodes) > 0) {
            $random = md5(date('Y-m-d H:i:s'));
            $mform->addElement('html', "<a href=\"#\" class=\"btn btn-secondary\" onclick=\"$('#block_enrolcode_old_codes-" . $random . "').toggleClass('hidden'); return false;\" style=\"margin-left: 10px;\">" . get_string('show_existing_codes', 'block_enrolcode') . "</a>");
            $table = array('<table class="generaltable">');
            $table[] = '    <tr>';
            $table[] = '        <th>' . get_string('code:accesscode', 'block_enrolcode') . '</th>';
            $table[] = '        <th>' . get_string('role') . '</th>';
            $table[] = '        <th>' . get_string('group') . '</th>';
            $table[] = '        <th>' . get_string('maturity', 'block_enrolcode') . '</th>';
            $table[] = '    </tr>';
            foreach ($oldcodes AS $oldcode) {
                $role = $DB->get_record('role', array('id' => $oldcode->roleid));
                $group = $DB->get_record('groups', array('id' => $oldcode->groupid));
                $table[] = '    <tr>';
                $table[] = '        <td>';
                $table[] = '            <img src="' . $CFG->wwwroot . '/blocks/enrolcode/pix/qr.php?format=base64&txt=' . base64_encode($oldcode->code) . '" width="20" />';
                $table[] = '            <a href="' . $CFG->wwwroot . '/blocks/enrolcode/enrol.php?code=' . $oldcode->code . '" target="_blank">' . $oldcode->code . '</a>';
                $table[] = '        </td>';
                $table[] = '        <td>' . (!empty($role->name) ? $role->name : $role->shortname) . '</td>';
                $table[] = '        <td>' . (!empty($group->id) ? $group->name : '-') . '</td>';
                $table[] = '        <td>' . (!empty($oldcode->maturity) ? date('Y-m-d H:i:s', $oldcode->maturity) : get_string('maturity:immediately', 'block_enrolcode')) . '</td>';
                $table[] = '    </tr>';
            }
            $table[] = '</table>';
            $mform->addElement('html', "<div id=\"block_enrolcode_old_codes-" . $random . "\" class=\"hidden\" style=\"margin-top: 10px;\">" . implode("\n", $table) . "</div>");
        }

        // Unfortunately this does not work in modal, therefore afterwards we do it manually.
        $mform->hideIf('maturity', 'custommaturity', 'notchecked');
        // Next line hides dateselector in modal.
        $mform->addElement('html', '<script type="text/javascript"> ' . $onclick . '; $("[data-uniqid=\'custommaturity-' . $uniqid . '\']").closest("form").find("#id_maturity_calendar").remove(); </script>');


        $mform->disable_form_change_checker();
    }
}
