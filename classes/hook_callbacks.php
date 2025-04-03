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
 * @copyright  2020 Center for Learningmangement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_enrolcode;
class hook_callbacks {
    public static function before_standard_head_html_generation($hook): void {
        global $PAGE;
        if (get_config('block_enrolcode', 'link_user_nav') == 1) {
            $PAGE->requires->js_call_amd('block_enrolcode/main', 'injectMainmenuButton', array());
        }
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/user/index.php') > 0) {
            $courseid = optional_param('id', 0, PARAM_INT);
            $PAGE->requires->js_call_amd('block_enrolcode/main', 'injectButton', array($courseid));
        }
    }
}
