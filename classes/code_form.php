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

require_once("{$CFG->libdir}/formslib.php");
class code_form extends \moodleform {
    public function __construct(private int $courseid) {
        parent::__construct();
    }

    function definition() {
        global $DB;
        $courseid = $this->courseid;
        // @todo https://github.com/center-for-learning-management/eduvidual-src/issues/18
        $uniqid = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 10)), 0, 10);
        $oldcodes = $DB->get_records('block_enrolcode', ['courseid' => $courseid], 'maturity ASC');
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $context = \context_course::instance($courseid);
        $roles = get_assignable_roles($context);
        $mform->addElement('html', "<div id=\"enrolform-$uniqid\">");
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->setDefault('roleid', 5);
        $_groups = groups_get_all_groups($courseid);
        if ($_groups) {
            $groups = array();
            $groups[0] = get_string('none');
            foreach ($_groups as $group) {
                $groups[$group->id] = $group->name;
            }
            $mform->addElement('select', 'groupid', get_string('group'), $groups);
        } else {
            $mform->addElement('hidden', 'groupid');
        }
        $mform->setType('groupid', PARAM_INT);
        // No 'timezone' option: the selector then uses the user's timezone incl. DST, matching make_timestamp() in external::get().
        $startendargs = array(
            'startyear' => date("Y"),
            'stopyear' => date("Y") + 5,
            'step' => 5,
            'optional' => 0,
        );
        $groupcustommaturity = [];
        $groupenrolmentend = [];
        $groupcustommaturity[] =& $mform->createElement('date_time_selector', 'maturity', '' /*get_string('maturity', 'block_enrolcode')*/, $startendargs);
        $groupcustommaturity[] =& $mform->createElement('checkbox', 'custommaturity', '', NULL, array('data-uniqid' => 'custommaturity-' . $uniqid));
        $groupenrolmentend[] =& $mform->createElement('date_time_selector', 'enrolmentend', '' /* get_string('enrolmentend:short', 'block_enrolcode') */, $startendargs);
        $groupenrolmentend[] =& $mform->createElement('checkbox', 'chkenrolmentend', '', NULL, array('data-uniqid' => 'chkenrolmentend-' . $uniqid));
        $mform->setDefault('custommaturity', 1);
        $mform->setType('custommaturity', PARAM_INT);
        $mform->setDefault('maturity', time() + 60 * 60 * 24 * 7);
        $mform->setType('chkenrolmentend', PARAM_INT);
        $notice = get_string('custommaturity:notice:no_maturity', 'block_enrolcode');
        $mform->addElement('html', '<p class="alert alert-info">' . $notice . '</p>');
        $mform->addGroup($groupcustommaturity, 'groupcustommaturity', get_string('custommaturity', 'block_enrolcode'), '', false);
        $mform->addGroup($groupenrolmentend, 'groupenrolmentend', get_string('enrolmentend', 'block_enrolcode'), '', false);
        $mform->addElement('html', "<a href=\"#\" class=\"btn btn-primary\" onclick=\"var btn = this; require(['block_enrolcode/main'], function(MAIN) { MAIN.getCode(btn); }); return false;\">" . get_string('create') . "</a>");
        if (count($oldcodes) > 0) {
            $mform->addElement('html', "<a href=\"#\" class=\"btn btn-secondary\" onclick=\"require(['jquery'], function($) { $('#block_enrolcode_old_codes-" . $uniqid . "').toggleClass('hidden'); }); return false;\" style=\"margin-left: 10px;\">" . get_string('show_existing_codes', 'block_enrolcode') . "</a>");
        }
        $mform->addElement('html', "</div>"); // end div enrolform-uniqid
        if ($oldcodes) {
            ob_start();
            ?>
            <div id="block_enrolcode_old_codes-<?= $uniqid ?>" class="hidden" style="margin-top: 10px;">
                <table class="generaltable">
                    <tr>
                        <th>&nbsp;</th>
                        <th><?= get_string('code:accesscode', 'block_enrolcode') ?></th>
                        <th><?= get_string('role') ?></th>
                        <th><?= get_string('group') ?></th>
                        <th><?= get_string('maturity', 'block_enrolcode') ?></th>
                        <th><?= get_string('enrolmentend:short', 'block_enrolcode') ?></th>
                    </tr>
                    <?php foreach (array_values($oldcodes) as $a => $oldcode): ?>
                        <?php $group = $_groups[$oldcode->groupid] ?? null; ?>
                        <tr id="enrolcode-item-<?= $uniqid ?>-<?= $a ?>" class="code" data-uniqid="<?= $uniqid ?>" data-code="<?= $oldcode->code ?>">
                            <td>
                                <a href="#" onclick="require(['block_enrolcode/main'], function(M) { M.fullsizeCode('<?= $uniqid ?>', <?= $a ?>); }); return false;"><i class="fa fa-expand"></i></a>
                                <a href="#" onclick="require(['block_enrolcode/main'], function(M) { M.deleteCode('<?= $oldcode->code ?>', '<?= $uniqid ?>'); }); return false;"><i class="fa fa-trash"></i></a>
                            </td>
                            <td>
                                <img class="qr" src="<?= api::get_qr_url($oldcode->code) ?>" width="20" />
                                <a href="<?= api::get_enrol_url($oldcode->code) ?>" target="_blank" class="accesscode"><?= $oldcode->code ?></a>
                            </td>
                            <td class="role"><?= $roles[$oldcode->roleid] ?? '-' ?></td>
                            <td class="group"><?= $group ? format_string($group->name) : '-' ?></td>
                            <td class="maturity"><?= $oldcode->maturity ? userdate($oldcode->maturity, '%Y-%m-%d %H:%M:%S', 99, false, false) : get_string('maturity:immediately', 'block_enrolcode') ?></td>
                            <td class="enrolmentend"><?= $oldcode->enrolmentend ? userdate($oldcode->enrolmentend, '%Y-%m-%d %H:%M:%S', 99, false, false) : get_string('enrolmentend:never', 'block_enrolcode') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php
            $mform->addElement('html', ob_get_clean());
        }
        // $mform->hideIf() does not work when the form is loaded into a modal, therefore the
        // date selectors are enabled/disabled by their checkboxes manually.
        ob_start();
        ?>
        <script>
            require(['jquery'], function($) {
                var form = $('[data-uniqid="custommaturity-<?= $uniqid ?>"]').closest('form');
                [['custommaturity', 'groupcustommaturity'], ['chkenrolmentend', 'groupenrolmentend']].forEach(function(pair) {
                    var checkbox = form.find('[name="' + pair[0] + '"]');
                    var toggle = function() {
                        form.find('#fgroup_id_' + pair[1] + ' select').prop('disabled', !checkbox.is(':checked'));
                    };
                    checkbox.on('change', toggle);
                    toggle();
                });
                // The calendar popups don't work inside the modal.
                form.find('#id_maturity_calendar, #id_enrolmentend_calendar').remove();
            });
        </script>
        <?php
        $mform->addElement('html', ob_get_clean());
        $mform->disable_form_change_checker();
    }
}
