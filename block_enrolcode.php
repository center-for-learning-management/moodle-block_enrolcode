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

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once(__DIR__ . '/locallib.php');

class block_enrolcode extends block_base {
    /**
     * Checks whether or not block_eduvidual is installed
     * @return true or false
    **/
    public static function uses_eduvidual(){
        global $CFG;
        return file_exists($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
    }
    public function init() {
        $this->title = get_string('code:accesscode', 'block_enrolcode');
    }
    public function get_content() {
        global $COURSE, $OUTPUT, $PAGE;

        $PAGE->requires->css('/blocks/enrolcode/style/enrolcode.css');

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) array('text' => '');
        // If course is dashboard =courseid 1 or is not enrolled in course show enter form for code
        if ($COURSE->id == 1 || !block_enrolcode_lib::is_enrolled()) {
            $this->content->text = $OUTPUT->render_from_template("block_enrolcode/code_enter", array());
        }
        // If is teacher of course show button to create a code that is displayed in a modal.
        if ($COURSE->id > 1 && block_enrolcode_lib::is_trainer()) {
            $this->content->text = block_enrolcode_lib::create_form($COURSE->id);
        }

        return $this->content;
    }
    public function hide_header() {
        return true;
    }
    public function has_config() {
        return true;
    }
    public function instance_allow_multiple() {
        return false;
    }
}
